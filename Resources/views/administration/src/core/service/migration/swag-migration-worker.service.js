import { Application } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import StorageBroadcastService from '../storage-broadcaster.service';
import { WorkerRequest } from './swag-migration-worker-request.service';
import {
    MIGRATION_STATUS,
    WorkerStatusManager
} from './swag-migration-worker-status-manager.service';


class MigrationWorkerService {
    constructor(
        migrationService,
        migrationDataService,
        migrationRunService,
        migrationMediaFileService,
        migrationLoggingService
    ) {
        this._MAX_REQUEST_TIME = 10000; // in ms
        this._ASSET_UUID_CHUNK = 100; // Amount of uuids we fetch with one request
        this._ASSET_WORKLOAD_COUNT = 5; // The amount of assets we download per request in parallel
        // The maximum amount of bytes we download per file in one request
        this._ASSET_FILE_CHUNK_BYTE_SIZE = 1000 * 1000 * 8; // 8 MB
        this._CHUNK_SIZE_BYTE_INCREMENT = 250 * 1000; // 250 KB
        this._ASSET_MIN_FILE_CHUNK_BYTE_SIZE = this._CHUNK_SIZE_BYTE_INCREMENT;

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
        this._workerStatusManager = new WorkerStatusManager(this._migrationRunService, this._migrationDataService);
        this._workerRequest = null;

        // state variables
        this._isMigrating = false;
        this._errors = [];
        this._entityGroups = [];
        this._progressSubscriber = null;
        this._statusSubscriber = null;
        this._runId = '';
        this._profile = null;
        this._status = null;
        this._assetTotalCount = 0;
        this._assetUuidPool = [];
        this._assetWorkload = [];
        this._assetProgress = 0;
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
     * Check if the last migration was not finished.
     * Resolves with true or false.
     *
     * @returns {Promise}
     */
    checkForRunningMigration() {
        return new Promise((resolve) => {
            this._migrationService.getState().then((state) => {
                if (state.migrationRunning === true) {
                    this._restoreState = state;
                    this._runId = this._restoreState.runId;
                    resolve(true);
                    return;
                }
                this._restoreState = {};
                resolve(false);
            }).catch(() => {
                this._restoreState = {};
                resolve(false);
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
        this._resetAssetProgress();

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
        this._workerRequest.interrupt = true;
        this._isMigrating = false;
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

    _callStatusSubscriber(param) {
        if (!this._isMigrating) {
            return;
        }
        if (this._statusSubscriber !== null) {
            this._statusSubscriber.call(null, param);
        }
    }

    _callProgressSubscriber(param) {
        if (!this._isMigrating) {
            return;
        }
        if (this._progressSubscriber !== null) {
            this._progressSubscriber.call(null, param);
        }
    }

    /**
     * @param {String} runId
     * @param {Object} profile
     * @param {Object} entityGroups
     * @param {int} statusIndex
     * @param {int} groupStartIndex
     * @param {int} entityStartIndex
     * @param {int} entityOffset
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
            this._isMigrationRunningInOtherTab()
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
                        runUuid: this._runId,
                        profileId: this._profile.id,
                        profileName: this._profile.profile,
                        gateway: this._profile.gateway,
                        credentialFields: this._profile.credentialFields
                    };
                    this._workerRequest = new WorkerRequest(
                        MIGRATION_STATUS.FETCH_DATA,
                        requestParams,
                        this._workerStatusManager,
                        this._migrationService,
                        this._callProgressSubscriber.bind(this),
                        this._addError.bind(this)
                    );

                    // fetch
                    if (statusIndex <= MIGRATION_STATUS.FETCH_DATA) {
                        await this._startWorkerRequest(
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
                        await this._startWorkerRequest(
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
                        // this._status = MIGRATION_STATUS.DOWNLOAD_DATA;
                        // this._callStatusSubscriber({ status: this.status });
                        // TODO: Implement download worker
                        await this._downloadData(entityOffset);

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
     * Start the WorkerRequest.
     *
     * @param {int} status
     * @param {int} groupStartIndex
     * @param {int} entityStartIndex
     * @param {int} entityOffset
     * @returns {Promise}
     * @private
     */
    _startWorkerRequest(status, groupStartIndex, entityStartIndex, entityOffset) {
        return new Promise(async (resolve) => {
            if (!this._isMigrating) {
                resolve();
                return;
            }

            this._status = status;
            this._workerRequest.status = this._status;

            if (groupStartIndex === 0 && entityStartIndex === 0 && entityOffset === 0) {
                this._resetProgress();
                this._callStatusSubscriber({ status: this.status });
            }

            await this._workerRequest.migrateProcess(
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
    _isMigrationRunningInOtherTab() {
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
     * @param data
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

    _downloadData(downloadFinishedCount = 0) {
        if (!this._isMigrating) {
            return Promise.resolve();
        }
        return this._getAssetTotalCount().then(() => {
            if (downloadFinishedCount === 0) {
                this._resetProgress();
                this._resetAssetProgress();
            } else {
                this._assetTotalCount += downloadFinishedCount; // we need to add the downloaded / finished count
                this._assetProgress += downloadFinishedCount;
                this._assetUuidPool = [];
                this._assetWorkload = [];
            }
            this._status = MIGRATION_STATUS.DOWNLOAD_DATA;
            this._callStatusSubscriber({ status: this.status });
            return this._downloadProcess();
        });
    }

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

    _getErrors() {
        return new Promise((resolve) => {
            const criteria = CriteriaFactory.term('runId', this._runId);
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

    _resetAssetProgress() {
        this._assetUuidPool = [];
        this._assetWorkload = [];
        this._assetProgress = 0;
    }

    /**
     * Get the count of media objects that are available for the migration.
     *
     * @returns {Promise}
     * @private
     */
    _getAssetTotalCount() {
        return new Promise((resolve) => {
            const count = {
                mediaCount: {
                    count: { field: 'swag_migration_media_file.mediaId' }
                }
            };
            const criteria = CriteriaFactory.multi(
                'AND',
                CriteriaFactory.equals('runId', this._runId),
                CriteriaFactory.equals('written', true),
                CriteriaFactory.equals('downloaded', false)
            );
            const params = {
                aggregations: count,
                criteria: criteria,
                limit: 1
            };

            this._migrationMediaFileService.getList(params).then((res) => {
                this._assetTotalCount = parseInt(res.aggregations.mediaCount.count, 10);
                resolve();
            }).catch(() => {
                this._assetTotalCount = 0;
                resolve();
            });
        });
    }

    /**
     * Get a chunk of asset uuids and put it into our pool.
     *
     * @returns {Promise}
     * @private
     */
    _fetchAssetUuidsChunk() {
        return new Promise((resolve) => {
            if (this._assetUuidPool.length >= this._ASSET_WORKLOAD_COUNT) {
                resolve();
                return;
            }

            this._migrationService.fetchAssetUuids({
                runId: this._runId,
                limit: this._ASSET_UUID_CHUNK
            }).then((res) => {
                res.mediaUuids.forEach((uuid) => {
                    let isInWorkload = false;
                    this._assetWorkload.forEach((media) => {
                        if (media.uuid === uuid) {
                            isInWorkload = true;
                        }
                    });

                    if (!isInWorkload && !this._assetUuidPool.includes(uuid)) {
                        this._assetUuidPool.push(uuid);
                    }
                });
                resolve();
            });
        });
    }

    /**
     * Download all media files to filesystem
     *
     * @returns {Promise}
     * @private
     */
    async _downloadProcess() {
        /* eslint-disable no-await-in-loop */
        return new Promise(async (resolve) => {
            await this._fetchAssetUuidsChunk();

            // make workload
            this._makeWorkload(this._ASSET_WORKLOAD_COUNT);

            while (this._assetProgress < this._assetTotalCount) {
                if (!this._isMigrating) {
                    resolve();
                    return;
                }
                // send workload to api
                let newWorkload;
                const beforeRequestTime = new Date();

                await this._downloadAssets().then((w) => {
                    newWorkload = w;
                });

                const afterRequestTime = new Date();
                // process response and update local workload
                this._updateWorkload(newWorkload, afterRequestTime - beforeRequestTime);

                await this._fetchAssetUuidsChunk();

                if (this._assetUuidPool.length === 0 && newWorkload.length === 0) {
                    break;
                }
            }

            resolve();
        });
        /* eslint-enable no-await-in-loop */
    }

    /**
     * Push asset uuids from the pool into the current workload
     *
     * @param assetCount the amount of uuids to add
     * @private
     */
    _makeWorkload(assetCount) {
        const uuids = this._assetUuidPool.splice(0, assetCount);
        uuids.forEach((uuid) => {
            this._assetWorkload.push({
                runId: this._runId,
                uuid,
                currentOffset: 0,
                state: 'inProgress'
            });
        });
    }

    /**
     * Analyse the given workload and update our own workload.
     * Remove finished assets from our workload and add new ones.
     * Remove failed assets (errorCount >= this._ASSET_ERROR_THRESHOLD) and add errors for them.
     * Make sure we have the asset amount in our workload that we specified (this._ASSET_WORKLOAD_COUNT).
     *
     * @param newWorkload
     * @param requestTime
     * @private
     */
    _updateWorkload(newWorkload, requestTime) {
        const finishedAssets = newWorkload.filter((asset) => asset.state === 'finished');
        let assetsRemovedCount = finishedAssets.length;

        // check for errorCount
        newWorkload.forEach((asset) => {
            if (asset.state === 'error') {
                assetsRemovedCount += 1;
            }
        });

        this._assetWorkload = newWorkload.filter((asset) => asset.state === 'inProgress');

        // Get the assets that have utilized the full amount of fileByteChunkSize
        const assetsWithoutAnyErrors = this._assetWorkload.filter((asset) => !asset.errorCount);
        if (assetsWithoutAnyErrors.length !== 0) {
            this._handleAssetFileChunkByteSize(requestTime);
        }

        this._assetProgress += assetsRemovedCount;
        // call event subscriber
        this._callProgressSubscriber({
            entityName: 'media',
            entityGroupProgressValue: this._assetProgress,
            entityCount: this._assetTotalCount
        });

        this._makeWorkload(assetsRemovedCount);
    }

    /**
     * Send the asset download request with our workload and fileChunkByteSize.
     *
     * @returns {Promise}
     * @private
     */
    _downloadAssets() {
        return new Promise((resolve) => {
            this._migrationService.downloadAssets({
                runId: this._runId,
                workload: this._assetWorkload,
                fileChunkByteSize: this._ASSET_FILE_CHUNK_BYTE_SIZE
            }).then((res) => {
                resolve(res.workload);
            }).catch(() => {
                resolve(this._assetWorkload);
            });
        });
    }

    /**
     * Update the ASSET_FILE_CHUNK_BYTE_SIZE depending on the requestTime
     *
     * @param {int} requestTime Request time in milliseconds
     * @private
     */
    _handleAssetFileChunkByteSize(requestTime) {
        if (requestTime < this._MAX_REQUEST_TIME) {
            this._ASSET_FILE_CHUNK_BYTE_SIZE += this._CHUNK_SIZE_BYTE_INCREMENT;
        }

        if (
            requestTime > this._MAX_REQUEST_TIME &&
            (this._ASSET_FILE_CHUNK_BYTE_SIZE - this._CHUNK_SIZE_BYTE_INCREMENT) >= this._ASSET_MIN_FILE_CHUNK_BYTE_SIZE
        ) {
            this._ASSET_FILE_CHUNK_BYTE_SIZE -= this._CHUNK_SIZE_BYTE_INCREMENT;
        }
    }

    _addError(error) {
        this._errors.push(error);
    }

    get applicationRoot() {
        if (this._applicationRoot) {
            return this._applicationRoot;
        }
        this._applicationRoot = Application.getApplicationRoot();
        return this._applicationRoot;
    }
}

export default MigrationWorkerService;
