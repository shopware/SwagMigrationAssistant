import { Application } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import StorageBroadcastService from '../storage-broadcaster.service';
import { WorkerRequest } from './swag-migration-worker-request.service';
import { WorkerDownload } from './swag-migration-worker-download.service';
import { MIGRATION_STATUS, WorkerStatusManager } from './swag-migration-worker-status-manager.service';

export const MIGRATION_ACCESS_TOKEN_NAME = 'swagMigrationAccessToken';

class MigrationWorkerService {
    /**
     * @param {MigrationApiService} migrationService
     * @param {MigrationDataService} migrationDataService
     * @param {MigrationRunService} migrationRunService
     * @param {MigrationMediaFileService} migrationMediaFileService
     * @param {MigrationLoggingService} migrationLoggingService
     */
    constructor(
        migrationService,
        migrationDataService,
        migrationRunService,
        migrationMediaFileService,
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
        this._migrationDataService = migrationDataService;
        this._migrationMediaFileService = migrationMediaFileService;
        this._migrationRunService = migrationRunService;
        this._migrationLoggingService = migrationLoggingService;
        this._workerStatusManager = new WorkerStatusManager(
            this._migrationRunService,
            this._migrationDataService,
            this._migrationMediaFileService
        );
        this._workRunner = null;

        // state variables
        this._isMigrating = false;
        this._errors = [];
        this._entityGroups = [];
        this._progressSubscriber = null;
        this._statusSubscriber = null;
        this._interruptSubscriber = null;
        this._runId = '';
        this._profile = null;
        this._status = null;
        this._restoreState = {};

        this._broadcastService.sendMessage({
            migrationMessage: 'initialized'
        });
    }

    /**
     * @returns {null|int}
     */
    get status() {
        return this._status;
    }

    /**
     * @param {null|int} value
     */
    set status(value) {
        this._status = value;
    }

    /**
     * @returns {string}
     */
    get runId() {
        return this._runId;
    }

    /**
     * @returns {boolean}
     */
    get isMigrating() {
        return this._isMigrating;
    }

    /**
     * @returns {Array}
     */
    get entityGroups() {
        return this._entityGroups;
    }

    /**
     * @returns {Array}
     */
    get errors() {
        return this._errors;
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

    _onInterrupt() {
        this._isMigrating = false;
        this._callInterruptSubscriber();
    }

    /**
     * Check if the last migration was not finished and the accessToken is valid.
     * Resolves with run state object:
     * { isMigrationRunning: {bool}, isMigrationAccessTokenValid: {bool}, status: {object|null} }
     *
     * @returns {Promise}
     */
    checkForRunningMigration() {
        return new Promise((resolve) => {
            const returnValue = { isMigrationRunning: false, isMigrationAccessTokenValid: false, status: null };
            this._migrationService.getState({
                swagMigrationAccessToken: MigrationWorkerService.migrationAccessToken
            }).then((state) => {
                this._restoreState = state;

                if (state.validMigrationRunToken === false) {
                    this._runId = state.runId;
                    returnValue.isMigrationRunning = true;
                    returnValue.status = state.status;

                    resolve(returnValue);
                    return;
                }

                if (state.migrationRunning === true) {
                    this._runId = this._restoreState.runId;
                    returnValue.isMigrationRunning = true;
                    returnValue.isMigrationAccessTokenValid = true;
                    returnValue.status = state.status;

                    resolve(returnValue);
                    return;
                }

                returnValue.isMigrationAccessTokenValid = true;
                resolve(returnValue);
            }).catch(() => {
                returnValue.isMigrationAccessTokenValid = true;
                this._restoreState = {};
                resolve(returnValue);
            });
        });
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

        this._runId = this._restoreState.runId;
        this._profile = this._restoreState.profile;
        this._entityGroups = this._restoreState.entityGroups;
        this._status = this._restoreState.status;
        this._errors = [];

        // Get current group and entity index
        const indicies = this._getIndiciesByEntityName(this._restoreState.entity);

        this.startMigration(
            this._runId,
            this._profile,
            this._entityGroups,
            this._status,
            indicies.groupIndex,
            indicies.entityIndex,
            this._restoreState.finishedCount
        );
    }

    stopMigration() {
        this._workRunner.interrupt = true;
        this._isMigrating = false;
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
        for (let groupIndex = 0; groupIndex < this._entityGroups.length; groupIndex += 1) {
            for (let entityIndex = 0; entityIndex < this._entityGroups[groupIndex].entities.length; entityIndex += 1) {
                if (this._entityGroups[groupIndex].entities[entityIndex].entityName === entityName) {
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
    subscribeStatus(callback) {
        this._statusSubscriber = callback;
    }

    unsubscribeStatus() {
        this._statusSubscriber = null;
    }

    /**
     * @param {function} callback
     */
    subscribeProgress(callback) {
        this._progressSubscriber = callback;
    }

    unsubscribeProgress() {
        this._progressSubscriber = null;
    }

    /**
     * @param {Object} param
     * @private
     */
    _callStatusSubscriber(param) {
        if (!this._isMigrating) {
            return;
        }
        if (this._statusSubscriber !== null) {
            this._statusSubscriber.call(null, param);
        }
    }

    /**
     * @param {Object} param
     * @private
     */
    _callProgressSubscriber(param) {
        if (!this._isMigrating) {
            return;
        }
        if (this._progressSubscriber !== null) {
            this._progressSubscriber.call(null, param);
        }
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
    _callInterruptSubscriber() {
        if (this._interruptSubscriber !== null) {
            this._interruptSubscriber.call(null);
        }
    }

    /**
     * @param {String} runId
     * @param {Object} profile
     * @param {Object} entityGroups
     * @param {number} statusIndex
     * @param {number} groupStartIndex
     * @param {number} entityStartIndex
     * @param {number} entityOffset
     * @returns {Promise}
     */
    startMigration(
        runId,
        profile,
        entityGroups,
        statusIndex = 0,
        groupStartIndex = 0,
        entityStartIndex = 0,
        entityOffset = 0
    ) {
        return new Promise((resolve, reject) => {
            if (this._isMigrating) {
                reject();
                return;
            }

            // Wait for the 'migrationWanted' request and response to allow or deny the migration
            this.isMigrationRunningInOtherTab()
                .then(async (isRunningInOtherTab) => {
                    if (isRunningInOtherTab) {
                        reject();
                        return;
                    }

                    this._isMigrating = true;
                    this._runId = runId;
                    this._profile = profile;
                    this._entityGroups = entityGroups;
                    this._errors = [];

                    const requestParams = {
                        swagMigrationAccessToken: MigrationWorkerService.migrationAccessToken,
                        runUuid: this._runId,
                        profileId: this._profile.id,
                        profileName: this._profile.profile,
                        gateway: this._profile.gateway,
                        credentialFields: this._profile.credentialFields
                    };

                    const downloadParams = {
                        swagMigrationAccessToken: MigrationWorkerService.migrationAccessToken,
                        runUuid: this._runId
                    };

                    this._workRunner = new WorkerRequest(
                        MIGRATION_STATUS.FETCH_DATA,
                        requestParams,
                        this._workerStatusManager,
                        this._migrationService,
                        this._callProgressSubscriber.bind(this),
                        this._addError.bind(this),
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
                    if (statusIndex <= MIGRATION_STATUS.DOWNLOAD_DATA) {
                        this._workRunner = new WorkerDownload(
                            MIGRATION_STATUS.DOWNLOAD_DATA,
                            downloadParams,
                            this._workerStatusManager,
                            this._migrationService,
                            this._callProgressSubscriber.bind(this),
                            this._addError.bind(this),
                            this._onInterrupt.bind(this)
                        );

                        await this._startWorkRunner(
                            MIGRATION_STATUS.DOWNLOAD_DATA,
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
                    this._isMigrating = false;
                    resolve(this._errors);
                });
        });
    }

    /**
     * Start the WorkerRequest or WorkerDownload runner.
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
            if (!this._isMigrating) {
                resolve();
                return;
            }

            this._status = status;
            this._workRunner.status = this._status;

            if (groupStartIndex === 0 && entityStartIndex === 0 && entityOffset === 0) {
                this._resetProgress();
                this._callStatusSubscriber({ status: this.status });
            }

            await this._workRunner.migrateProcess(
                this._entityGroups,
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
            }, 100);
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
            if (this.isMigrating) {
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
        if (!this._isMigrating) {
            return Promise.resolve();
        }

        return this._getErrors().then(() => {
            this._migrationRunService.updateById(this._runId, { status: 'finished' });
            this._resetProgress();
            this._status = MIGRATION_STATUS.FINISHED;
            this._callStatusSubscriber({ status: this.status });

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
            const criteria = CriteriaFactory.equals('runId', this._runId);
            const params = {
                criteria: criteria
            };

            this._migrationLoggingService.getList(params).then((response) => {
                const logs = response.data;
                logs.forEach((log) => {
                    if (log.type === 'warning' || log.type === 'error') {
                        this._addError({
                            code: log.logEntry.code,
                            detail: log.logEntry.description,
                            description: log.logEntry.description,
                            details: log.logEntry.details
                        });
                    }
                });

                resolve();
            });
        });
    }

    _resetProgress() {
        this._entityGroups.forEach((group) => {
            group.progress = 0;
        });

        this._syncProgressWithUI();
    }

    /**
     * Call the UI callback to update all the progress bar values.
     *
     * @private
     */
    _syncProgressWithUI() {
        for (let groupIndex = 0; groupIndex < this._entityGroups.length; groupIndex += 1) {
            const group = this._entityGroups[groupIndex];
            if (group.entities[0] !== undefined) {
                const entity = group.entities[0];
                this._callProgressSubscriber({
                    entityName: entity.entityName,
                    entityGroupProgressValue: group.progress,
                    entityCount: entity.entityCount
                });
            }
        }
    }

    _addError(error) {
        this._errors.push(error);
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
