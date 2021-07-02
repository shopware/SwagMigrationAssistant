import { WORKER_INTERRUPT_TYPE } from './swag-migration-worker.service';

const { Application, State } = Shopware;

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
    3: 'processMedia',
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
        onInterruptCB,
    ) {
        this._MAX_REQUEST_TIME = 10000; // in ms
        this._DEFAULT_CHUNK_SIZE = 25; // in data sets

        // how much does the chunk factor manipulate the chunk size for under target request times
        this._CHUNK_PROPORTION_UP_WEIGHT = 0.1;

        // how much does the chunk factor manipulate the chunk size for above target request times
        this._CHUNK_PROPORTION_DOWN_WEIGHT = 0.9;

        this._migrationProcessState = State.get('swagMigration/process');
        this._runId = requestParams.runUuid;
        this._requestParams = requestParams;
        this._workerStatusManager = workerStatusManager;
        this._migrationService = migrationService;
        this._interrupt = '';
        this._chunkSize = this._DEFAULT_CHUNK_SIZE;
        this._lastChunkSize = this._DEFAULT_CHUNK_SIZE;
        this._successfulChunk = this._DEFAULT_CHUNK_SIZE;

        // callbacks
        this._onInterruptCB = onInterruptCB;
    }

    /**
     * @returns {string}
     */
    get operation() {
        return WORKER_API_OPERATION[this._migrationProcessState.statusIndex];
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
     * @param {number} statusIndex
     * @param {number} groupStartIndex
     * @param {number} entityStartIndex
     * @param {number} entityOffset
     * @returns {Promise}
     */
    async migrateProcess(statusIndex, groupStartIndex = 0, entityStartIndex = 0, entityOffset = 0) {
        /* eslint-disable no-await-in-loop */
        return new Promise(async (resolve) => {
            let statusChangedError = false;
            await this._workerStatusManager.changeStatus(this._runId, statusIndex).catch(() => {
                statusChangedError = true;
            });

            if (statusChangedError === true) {
                this.interrupt = WORKER_INTERRUPT_TYPE.STOP;
                this._callInterruptCB();
                resolve();
                return;
            }

            // Reference to store state, don't mutate this!
            const entityGroups = this._migrationProcessState.entityGroups;
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
                            entityOffset,
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

            await this._migrateEntityRequest(entityName, currentOffset);
            let newOffset = currentOffset + this._successfulChunk;
            if (newOffset > entityCount) {
                newOffset = entityCount;
            }

            // update State
            State.commit('swagMigration/process/setEntityProgress', {
                groupId: group.id,
                groupCurrentCount: groupProgress + newOffset,
                groupTotal: group.total,
            });

            currentOffset += this._successfulChunk;
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
                        // Request timeout behavior: scale chunk size down on first occur and back to default on second
                        requestFailedCount += 1;
                        if (this._requestParams.limit === this._lastChunkSize) {
                            this._chunkSize = this._DEFAULT_CHUNK_SIZE;
                            this._requestParams.limit = this._chunkSize;
                        } else {
                            this._handleChunkSize();
                            this._lastChunkSize = this._chunkSize; // next time reset to default chunk size
                            this._requestParams.limit = this._chunkSize;
                        }
                        return;
                    }

                    if (response.validToken === undefined) {
                        // Memory limit behavior: If occurs the validToken is not in the response
                        // Then we scale the limit down to default and retry
                        // If the retry also fails with memory limit exceeded we skip this chunk
                        if (this._requestParams.limit === this._lastChunkSize) {
                            this._successfulChunk = this._requestParams.limit;
                            requestRetry = false;
                            return;
                        }

                        this._chunkSize = this._DEFAULT_CHUNK_SIZE;
                        this._lastChunkSize = this._chunkSize;
                        this._requestParams.limit = this._chunkSize;
                        return;
                    }

                    if (!response.validToken) {
                        this.interrupt = WORKER_INTERRUPT_TYPE.TAKEOVER;
                        this._successfulChunk = 0;
                        requestRetry = false;
                        return;
                    }

                    this._successfulChunk = this._requestParams.limit;
                    const afterRequestTime = new Date();
                    this._handleChunkSize(afterRequestTime.getTime() - beforeRequestTime.getTime());
                    requestRetry = false;
                }).catch((response) => {
                    if (!response || !response.response) {
                        // Request timeout behavior: scale chunk size down on first occur and back to default on second
                        requestFailedCount += 1;
                        if (this._requestParams.limit === this._lastChunkSize) {
                            this._chunkSize = this._DEFAULT_CHUNK_SIZE;
                            this._requestParams.limit = this._chunkSize;
                        } else {
                            this._handleChunkSize();
                            this._lastChunkSize = this._chunkSize; // next time reset to default chunk size
                            this._requestParams.limit = this._chunkSize;
                        }
                        return;
                    }

                    if (response.response.status === 500) {
                        // Don't retry if server errors happen, only if something is wrong with the connection.
                        this._successfulChunk = this._requestParams.limit;
                        requestRetry = false;
                        return;
                    }

                    const afterRequestTime = new Date();
                    this._handleChunkSize(afterRequestTime.getTime() - beforeRequestTime.getTime());

                    requestFailedCount += 1;
                });

                if (requestFailedCount >= 3) {
                    this._successfulChunk = 0;
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
     * @param {number} requestTime Request time in milliseconds (fallback to 30s for timeout simulation)
     * @private
     */
    _handleChunkSize(requestTime = 30000) {
        this._lastChunkSize = this._chunkSize;
        const factor = this._MAX_REQUEST_TIME / requestTime;

        if (requestTime < this._MAX_REQUEST_TIME) {
            // Scale chunk size up
            const weightedFactor = (factor - 1) * this._CHUNK_PROPORTION_UP_WEIGHT + 1;
            this._chunkSize = Math.ceil(this._chunkSize * weightedFactor);
            return;
        }

        // Scale chunk size down
        const weightedFactor = (factor - 1) * this._CHUNK_PROPORTION_DOWN_WEIGHT + 1;
        this._chunkSize = Math.ceil(this._chunkSize * weightedFactor);

        if (this._chunkSize < this._DEFAULT_CHUNK_SIZE) {
            this._chunkSize = this._DEFAULT_CHUNK_SIZE;
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
