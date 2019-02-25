import { Application, State } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import StorageBroadcastService from '../storage-broadcaster.service';
import { WorkerRequest } from './swag-migration-worker-request.service';
import { WorkerMediaFiles } from './swag-migration-worker-media-files.service';
import { MIGRATION_STATUS, WorkerStatusManager } from './swag-migration-worker-status-manager.service';

export const MIGRATION_ACCESS_TOKEN_NAME = 'swagMigrationAccessToken';

export const WORKER_INTERRUPT_TYPE = Object.freeze({
    TAKEOVER: 'takeover',
    STOP: 'stop',
    PAUSE: 'pause'
});

class MigrationWorkerService {
    /**
     * @param {MigrationApiService} migrationService
     * @param {MigrationRunService} migrationRunService
     * @param {MigrationLoggingService} migrationLoggingService
     */
    constructor(
        migrationService,
        migrationRunService,
        migrationLoggingService
    ) {
        // will be toggled when we receive a response for our 'migrationWanted' request
        this._broadcastResponseFlag = false;

        // handles cross browser tab communication
        this._broadcastService = new StorageBroadcastService(
            this._onBroadcastReceived.bind(this),
            'swag-migration-service'
        );

        this._migrationService = migrationService;
        this._migrationRunService = migrationRunService;
        this._migrationLoggingService = migrationLoggingService;
        this._workerStatusManager = new WorkerStatusManager(
            this._migrationService,
        );
        this._workRunner = null;

        /** @type MigrationProcessStore */
        this._migrationProcessStore = State.getStore('migrationProcess');
        // state variables
        this._interruptSubscriber = null;
        this._restoreState = {};

        this._broadcastService.sendMessage({
            migrationMessage: 'initialized'
        });
    }

    /**
     * Returns the accessToken out of the local storage
     *
     * @returns {string}
     */
    static get migrationAccessToken() {
        let token = localStorage.getItem(MIGRATION_ACCESS_TOKEN_NAME);

        if (token === null) {
            token = '';
        }

        return token;
    }

    _onInterrupt(value) {
        this._migrationProcessStore.setIsMigrating(false);
        this._callInterruptSubscriber(value);
    }

    /**
     * Check if the last migration was not finished, the accessToken is valid and set the restoreState.
     *
     * @returns {Promise<{
     *   runUuid: null,
     *   isMigrationRunning: boolean,
     *   isMigrationAccessTokenValid: boolean,
     *   status: object|null,
     *   accessToken: string|null
     * }>}
     */
    checkForRunningMigration() {
        return new Promise((resolve) => {
            this._migrationService.getState({
                swagMigrationAccessToken: MigrationWorkerService.migrationAccessToken
            }).then((state) => {
                resolve(this.processStateResponse(state));
            }).catch(() => {
                const returnValue = {
                    runUuid: null,
                    isMigrationRunning: false,
                    isMigrationAccessTokenValid: false,
                    status: null,
                    accessToken: null
                };

                returnValue.isMigrationAccessTokenValid = true;
                this._restoreState = {};
                resolve(returnValue);
            });
        });
    }

    /**
     * Try to create a new migration run and process the return state.
     *
     * @returns {Promise<{
     *   runUuid: null,
     *   isMigrationRunning: boolean,
     *   isMigrationAccessTokenValid: boolean,
     *   status: object|null,
     *   accessToken: string|null
     * }>}
     */
    createMigration(dataSelectionIds) {
        return new Promise((resolve) => {
            this._migrationService.createMigration(
                this._migrationProcessStore.state.connectionId,
                dataSelectionIds
            ).then((state) => {
                const returnState = this.processStateResponse(state);

                if (returnState.isMigrationRunning === false && returnState.isMigrationAccessTokenValid === true) {
                    this._migrationProcessStore.setStatusIndex(MIGRATION_STATUS.PREMAPPING);
                }

                this._migrationProcessStore.setRunId(returnState.runUuid);
                resolve(returnState);
            }).catch(() => {
                const returnValue = {
                    runUuid: null,
                    isMigrationRunning: false,
                    isMigrationAccessTokenValid: true,
                    status: null,
                    accessToken: null
                };

                this._restoreState = {};
                resolve(returnValue);
            });
        });
    }

    /**
     * Check if the migration was not finished, the accessToken is valid and set the restoreState.
     *
     * @param {Object} state
     * @return {{
     *          runUuid: null,
     *          isMigrationRunning: boolean,
     *          isMigrationAccessTokenValid: boolean,
     *          status: object|null,
     *          accessToken: string|null
     *          }}
     */
    processStateResponse(state) {
        const returnValue = {
            runUuid: null,
            isMigrationRunning: false,
            isMigrationAccessTokenValid: false,
            status: null,
            accessToken: null,
            runProgress: null
        };

        this._restoreState = state;
        returnValue.runUuid = state.runId;
        returnValue.accessToken = state.accessToken;
        returnValue.runProgress = state.runProgress;

        if (state.validMigrationRunToken === false) {
            this._migrationProcessStore.setRunId(state.runId);
            returnValue.isMigrationRunning = true;
            returnValue.status = state.status;

            return returnValue;
        }

        if (state.migrationRunning === true) {
            this._migrationProcessStore.setRunId(this._restoreState.runId);
            returnValue.isMigrationRunning = true;
            returnValue.isMigrationAccessTokenValid = true;
            returnValue.status = state.status;

            return returnValue;
        }

        returnValue.isMigrationAccessTokenValid = true;
        return returnValue;
    }

    /**
     * Continue the migration (possible after checkForRunningMigration resolved true).
     */
    restoreRunningMigration() {
        if (this._restoreState === null || this._restoreState === {}) {
            return;
        }

        if (this._restoreState.migrationRunning === false) {
            return;
        }

        this._migrationProcessStore.setRunId(this._restoreState.runId);
        this._migrationProcessStore.setEntityGroups(this._restoreState.runProgress);
        this._migrationProcessStore.setStatusIndex(this._restoreState.status);
        this._migrationProcessStore.setErrors([]);

        // Get current group and entity index
        const indicies = this._getIndiciesByEntityName(this._restoreState.entity);

        this.startMigration(
            this._migrationProcessStore.state.runId,
            this._migrationProcessStore.state.statusIndex,
            indicies.groupIndex,
            indicies.entityIndex,
            this._restoreState.finishedCount
        );
    }

    stopMigration() {
        if (this._workRunner === null) {
            this._callInterruptSubscriber(WORKER_INTERRUPT_TYPE.STOP);
            return;
        }

        this._workRunner.interrupt = WORKER_INTERRUPT_TYPE.STOP;
    }

    pauseMigration() {
        this._workRunner.interrupt = WORKER_INTERRUPT_TYPE.PAUSE;
    }

    /**
     * Takeover the current migration and save the given accessToken into the localStorage.
     *
     * @return {Promise}
     */
    takeoverMigration() {
        return new Promise((resolve) => {
            this._migrationService.takeoverMigration(this.runId).then((migrationAccessToken) => {
                localStorage.setItem(MIGRATION_ACCESS_TOKEN_NAME, migrationAccessToken.accessToken);
                resolve();
            });
        });
    }

    /**
     * Get the groupIndex and the entityIndex from the entityGroups for the specified entityName.
     *
     * @param {string} entityName
     * @returns {{groupIndex: number, entityIndex: number}}
     * @private
     */
    _getIndiciesByEntityName(entityName) {
        for (let groupIndex = 0; groupIndex < this._migrationProcessStore.state.entityGroups.length; groupIndex += 1) {
            for (let entityIndex = 0;
                entityIndex < this._migrationProcessStore.state.entityGroups[groupIndex].entities.length;
                entityIndex += 1
            ) {
                if (this._migrationProcessStore.state.entityGroups[groupIndex].entities[entityIndex].entityName === entityName) {
                    return {
                        groupIndex,
                        entityIndex
                    };
                }
            }
        }

        return {
            groupIndex: -1,
            entityIndex: -1
        };
    }

    /**
     * @param {function} callback
     */
    subscribeInterrupt(callback) {
        this._interruptSubscriber = callback;
    }

    unsubscribeInterrupt() {
        this._interruptSubscriber = null;
    }

    /**
     * @private
     */
    _callInterruptSubscriber(value) {
        if (this._interruptSubscriber !== null) {
            this._interruptSubscriber(value);
        }
    }

    /**
     * @param {String} runId
     * @param {number} statusIndex
     * @param {number} groupStartIndex
     * @param {number} entityStartIndex
     * @param {number} entityOffset
     * @returns {Promise}
     */
    startMigration(
        runId,
        statusIndex = MIGRATION_STATUS.FETCH_DATA,
        groupStartIndex = 0,
        entityStartIndex = 0,
        entityOffset = 0
    ) {
        return new Promise(async (resolve) => {
            // Wait for the 'migrationWanted' request and response to allow or deny the migration
            this._migrationProcessStore.setErrors([]);
            this._migrationProcessStore.setIsMigrating(true);
            this._migrationProcessStore.setRunId(runId);

            let processMediaFiles = false;
            this._migrationProcessStore.state.entityGroups.forEach((group) => {
                if (group.processMediaFiles) {
                    processMediaFiles = true;
                }
            });

            const params = {
                swagMigrationAccessToken: MigrationWorkerService.migrationAccessToken,
                runUuid: this._migrationProcessStore.state.runId
            };

            this._workRunner = new WorkerRequest(
                params,
                this._workerStatusManager,
                this._migrationService,
                this._onInterrupt.bind(this)
            );

            // fetch
            if (statusIndex <= MIGRATION_STATUS.FETCH_DATA) {
                await this._startWorkRunner(
                    MIGRATION_STATUS.FETCH_DATA,
                    groupStartIndex,
                    entityStartIndex,
                    entityOffset
                );

                groupStartIndex = 0;
                entityStartIndex = 0;
                entityOffset = 0;
            }

            // write
            if (statusIndex <= MIGRATION_STATUS.WRITE_DATA) {
                await this._startWorkRunner(
                    MIGRATION_STATUS.WRITE_DATA,
                    groupStartIndex,
                    entityStartIndex,
                    entityOffset
                );

                groupStartIndex = 0;
                entityStartIndex = 0;
                entityOffset = 0;
            }

            // download
            if (statusIndex <= MIGRATION_STATUS.PROCESS_MEDIA_FILES && processMediaFiles) {
                this._workRunner = new WorkerMediaFiles(
                    params,
                    this._workerStatusManager,
                    this._migrationService,
                    this._onInterrupt.bind(this)
                );

                await this._startWorkRunner(
                    MIGRATION_STATUS.PROCESS_MEDIA_FILES,
                    groupStartIndex,
                    entityStartIndex,
                    entityOffset
                );

                groupStartIndex = 0;
                entityStartIndex = 0;
                entityOffset = 0;
            }

            // finish
            await this._migrateFinish();
            this._migrationProcessStore.setIsMigrating(false);
            resolve(this._migrationProcessStore.state.errors);
        });
    }

    /**
     * Start the WorkerRequest or WorkerMediaFiles runner.
     *
     * @param {number} status
     * @param {number} groupStartIndex
     * @param {number} entityStartIndex
     * @param {number} entityOffset
     * @returns {Promise}
     * @private
     */
    _startWorkRunner(status, groupStartIndex, entityStartIndex, entityOffset) {
        return new Promise(async (resolve) => {
            if (!this._migrationProcessStore.state.isMigrating) {
                resolve();
                return;
            }

            this._migrationProcessStore.setStatusIndex(status);

            if (groupStartIndex === 0 && entityStartIndex === 0 && entityOffset === 0) {
                this._resetProgress();
            }

            await this._workRunner.migrateProcess(
                groupStartIndex,
                entityStartIndex,
                entityOffset
            );

            resolve();
        });
    }

    /**
     * Resolves with true if a migration is already running in another tab. otherwise false.
     * It will resolve after 100ms.
     *
     * @returns {Promise}
     * @private
     */
    isMigrationRunningInOtherTab() {
        return new Promise(async (resolve) => {
            this._broadcastService.sendMessage({
                migrationMessage: 'migrationWanted'
            });

            const oldFlag = this._broadcastResponseFlag;
            setTimeout(() => {
                if (this._broadcastResponseFlag !== oldFlag) {
                    resolve(true);
                    return;
                }

                resolve(false);
            }, 250);
        });
    }

    /**
     * Gets called with data from another browser tab
     *
     * @param {Object} data
     * @private
     */
    _onBroadcastReceived(data) {
        // answer incoming migration wanted request based on current migration state.
        if (data.migrationMessage === 'migrationWanted') {
            if (this._migrationProcessStore.state.isMigrating) {
                this._broadcastService.sendMessage({
                    migrationMessage: 'migrationDenied'
                });
            }
        }

        // allow own migration if no migrationDenied response comes back.
        if (data.migrationMessage === 'migrationDenied') {
            this._broadcastResponseFlag = !this._broadcastResponseFlag;
        }
    }

    /**
     * @returns {Promise}
     * @private
     */
    _migrateFinish() {
        if (!this._migrationProcessStore.state.isMigrating) {
            return Promise.resolve();
        }

        return this._getErrors().then(() => {
            this._migrationRunService.updateById(this._migrationProcessStore.state.runId, { status: 'finished' });
            this._resetProgress();
            this._migrationProcessStore.setStatusIndex(MIGRATION_STATUS.FINISHED);

            return Promise.resolve();
        });
    }

    /**
     * Update the local errors array with the errors from the backend.
     *
     * @returns {Promise}
     * @private
     */
    _getErrors() {
        return new Promise((resolve) => {
            const criteria = CriteriaFactory.equals('runId', this._migrationProcessStore.state.runId);
            const params = {
                criteria: criteria,
                limit: 500
            };

            this._migrationLoggingService.getList(params).then((response) => {
                const logs = response.data;
                logs.forEach((log) => {
                    if (log.type === 'warning' || log.type === 'error') {
                        this._addError({
                            code: log.logEntry.code,
                            detail: log.logEntry.description,
                            description: log.logEntry.description,
                            details: log.logEntry.details,
                            internalError: false
                        });
                    }
                });

                resolve();
            });
        });
    }

    _resetProgress() {
        this._migrationProcessStore.resetProgress();
    }

    _addError(error) {
        if (error.internalError && this._errorCodeExists(error.code)) {
            return;
        }

        this._migrationProcessStore.addError(error);
    }

    _errorCodeExists(errorCode) {
        for (let index = 0; index < this._migrationProcessStore.state.errors.length; index += 1) {
            if (errorCode === this._migrationProcessStore.state.errors[index].code) {
                return true;
            }
        }

        return false;
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

export default MigrationWorkerService;
