import { Application, State } from 'src/core/shopware';
import { WORKER_INTERRUPT_TYPE } from './swag-migration-worker.service';

/**
 * Describes the current API endpoint.
 * Can be easily used together with the MIGRATION_STATUS.
 *
 * Example to get the API operation from status:
 * WORKER_API_OPERATION[MIGRATION_STATUS.FETCH_DATA]
 *
 * @type {Readonly<{"0": string, "1": string, "2": string}>}
 */
export const WORKER_API_OPERATION = Object.freeze({
    1: 'fetchData',
    2: 'writeData',
    3: 'downloadMedia'
});

export class WorkerRequest {
    /**
     * @param {Object} requestParams
     * @param {WorkerStatusManager} workerStatusManager
     * @param {MigrationApiService} migrationService
     * @param {function} onInterruptCB
     */
    constructor(
        requestParams,
        workerStatusManager,
        migrationService,
        onInterruptCB
    ) {
        this._MAX_REQUEST_TIME = 10000; // in ms
        this._DEFAULT_CHUNK_SIZE = 25; // in data sets
        this._CHUNK_PROPORTION_BUFFER = 0.8; // chunk buffer

        this._migrationProcessStore = State.getStore('migrationProcess');
        this._runId = requestParams.runUuid;
        this._requestParams = requestParams;
        this._workerStatusManager = workerStatusManager;
        this._migrationService = migrationService;
        this._interrupt = '';
        this._chunkSize = this._DEFAULT_CHUNK_SIZE;

        // callbacks
        this._onInterruptCB = onInterruptCB;
    }

    /**
     * @returns {string}
     */
    get operation() {
        return WORKER_API_OPERATION[this._migrationProcessStore.state.statusIndex];
    }

    /**
     * @returns {boolean}
     */
    get interrupt() {
        return this._interrupt;
    }

    /**
     * @param {boolean} value
     */
    set interrupt(value) {
        this._interrupt = value;
    }

    /**
     * @param {function} value
     */
    set onInterruptCB(value) {
        this._callInterruptCB = value;
    }

    /**
     * @private
     */
    _callInterruptCB() {
        if (this._onInterruptCB !== null) {
            this._onInterruptCB(this._interrupt);
        }
    }

    /**
     * Do all the API requests for all entities with the given methodName
     *
     * @param {number} groupStartIndex
     * @param {number} entityStartIndex
     * @param {number} entityOffset
     * @returns {Promise}
     */
    async migrateProcess(groupStartIndex = 0, entityStartIndex = 0, entityOffset = 0) {
        /* eslint-disable no-await-in-loop */
        return new Promise(async (resolve) => {
            let statusChangedError = false;
            await this._workerStatusManager.onStatusChanged(this._runId).catch(() => {
                statusChangedError = true;
            });

            if (statusChangedError === true) {
                this.interrupt = WORKER_INTERRUPT_TYPE.STOP;
                this._callInterruptCB();
                resolve();
                return;
            }

            // Reference to store state, don't mutate this!
            const entityGroups = this._migrationProcessStore.state.entityGroups;
            for (let groupIndex = groupStartIndex; groupIndex < entityGroups.length; groupIndex += 1) {
                let groupProgress = 0;
                for (let entityIndex = 0; entityIndex < entityGroups[groupIndex].entities.length; entityIndex += 1) {
                    const entityName = entityGroups[groupIndex].entities[entityIndex].entityName;
                    const entityCount = entityGroups[groupIndex].entities[entityIndex].total;

                    if (entityIndex >= entityStartIndex) {
                        await this._migrateEntity(
                            entityName,
                            entityCount,
                            entityGroups[groupIndex],
                            groupProgress,
                            entityOffset
                        );

                        if (this._interrupt !== '') {
                            this._callInterruptCB();
                            resolve();
                            return;
                        }

                        entityOffset = 0;
                    }

                    groupProgress += entityCount;
                }
                entityStartIndex = 0;
            }

            resolve();
        });
        /* eslint-enable no-await-in-loop */
    }

    /**
     * Do all the API requests for one entity in chunks
     *
     * @param {string} entityName
     * @param {number} entityCount
     * @param {Object} group
     * @param {number} groupProgress
     * @param {number} currentOffset
     * @returns {Promise<void>}
     * @private
     */
    async _migrateEntity(entityName, entityCount, group, groupProgress, currentOffset = 0) {
        /* eslint-disable no-await-in-loop */
        while (currentOffset < entityCount) {
            if (this._interrupt !== '') {
                return;
            }

            const oldChunkSize = this._chunkSize;
            await this._migrateEntityRequest(entityName, currentOffset);
            let newOffset = currentOffset + oldChunkSize;
            if (newOffset > entityCount) {
                newOffset = entityCount;
            }

            // update State
            this._migrationProcessStore.setEntityProgress(
                entityName,
                groupProgress + newOffset,
                group.total
            );

            currentOffset += oldChunkSize;
        }
        /* eslint-enable no-await-in-loop */

        this._chunkSize = this._DEFAULT_CHUNK_SIZE;
    }

    /**
     * Do a single API request for the given entity with given offset.
     *
     * @param {string} entityName
     * @param {number} offset
     * @returns {Promise}
     * @private
     */
    _migrateEntityRequest(entityName, offset) {
        return new Promise(async (resolve) => {
            this._requestParams.entity = entityName;
            this._requestParams.offset = offset;
            this._requestParams.limit = this._chunkSize;

            let requestRetry = true;
            let requestFailedCount = 0;
            /* eslint-disable no-await-in-loop, no-loop-func */
            while (requestRetry) {
                const beforeRequestTime = new Date();
                await this._migrationService[this.operation](this._requestParams).then((response) => {
                    if (!response) {
                        this._migrationProcessStore.addError({
                            code: 'canNotConnectToServer',
                            internalError: true
                        });
                        requestFailedCount += 1;
                        return;
                    }

                    if (!response.validToken) {
                        this.interrupt = WORKER_INTERRUPT_TYPE.TAKEOVER;
                        requestRetry = false;
                        return;
                    }

                    const afterRequestTime = new Date();
                    this._handleChunkSize(afterRequestTime.getTime() - beforeRequestTime.getTime());
                    requestRetry = false;
                }).catch((response) => {
                    if (!response || !response.response) {
                        this._migrationProcessStore.addError({
                            code: 'canNotConnectToServer',
                            internalError: true
                        });
                        requestFailedCount += 1;
                        return;
                    }

                    if (response.response.data && response.response.data.errors) {
                        response.response.data.errors.forEach((error) => {
                            this._migrationProcessStore.addError(error);
                        });
                    }

                    const afterRequestTime = new Date();
                    this._handleChunkSize(afterRequestTime.getTime() - beforeRequestTime.getTime());

                    if (response.response.status === 500) {
                        // Don't retry if server errors happen, only if something is wrong with the connection.
                        requestRetry = false;
                        return;
                    }

                    this._migrationProcessStore.addError({
                        code: 'canNotConnectToServer',
                        internalError: true
                    });

                    requestFailedCount += 1;
                });

                if (requestFailedCount >= 3) {
                    requestRetry = false;
                    if (this.operation === WORKER_API_OPERATION[1]) {
                        this.interrupt = WORKER_INTERRUPT_TYPE.CONNECTION_LOST;
                    } else {
                        this.interrupt = WORKER_INTERRUPT_TYPE.PAUSE;
                    }
                }
            }
            /* eslint-enable no-await-in-loop, no-loop-func */

            resolve();
        });
    }

    /**
     * Update the chunkSize depending on the requestTime / maxRequestTime proportion
     *
     * @param {number} requestTime Request time in milliseconds
     * @private
     */
    _handleChunkSize(requestTime) {
        if (requestTime < this._MAX_REQUEST_TIME) {
            const factor = this._MAX_REQUEST_TIME / requestTime;
            this._chunkSize = Math.ceil((this._chunkSize * factor) * this._CHUNK_PROPORTION_BUFFER);
        }

        if (requestTime > this._MAX_REQUEST_TIME) {
            const ratio = 1 - requestTime / this._MAX_REQUEST_TIME;
            this._chunkSize = Math.ceil(this._chunkSize * (1 + ratio));

            if (this._chunkSize < this._DEFAULT_CHUNK_SIZE) {
                this._chunkSize = this._DEFAULT_CHUNK_SIZE;
            }
        }
    }

    /**
     * @returns {Boolean|Vue}
     */
    get applicationRoot() {
        if (this._applicationRoot) {
            return this._applicationRoot;
        }
        this._applicationRoot = Application.getApplicationRoot();
        return this._applicationRoot;
    }
}

export default WorkerRequest;
