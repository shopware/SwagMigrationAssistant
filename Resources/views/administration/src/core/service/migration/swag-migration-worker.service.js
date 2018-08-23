import StorageBroadcastService from '../storage-broadcaster.service';

class MigrationService {
    constructor(migrationService) {
        this._MAX_REQUEST_TIME = 2000; // in ms
        this._DEFAULT_CHUNK_SIZE = 50; // in data sets
        this._CHUNK_INCREMENT = 5; // in data sets

        this.MIGRATION_STATUS = {
            WAITING: -1,
            FETCH_DATA: 0,
            WRITE_DATA: 1,
            DOWNLOAD_DATA: 2,
            FINISHED: 3
        };

        // will be toggled when we receive a response for our 'migrationWanted' request
        this._broadcastResponseFlag = false;

        // handles cross browser tab communication
        this._broadcastService = new StorageBroadcastService(
            this._onBroadcastReceived.bind(this),
            'swag-migration-service'
        );

        this._migrationService = migrationService;
        this._chunkSize = this._DEFAULT_CHUNK_SIZE;

        // state variables
        this._isMigrating = false;
        this._errors = [];
        this._entityGroups = [];
        this._progressSubscriber = null;
        this._statusSubscriber = null;
        this._profile = null;
        this._status = null;

        this._broadcastService.sendMessage({
            migrationMessage: 'initialized'
        });
    }

    get status() {
        return this._status;
    }

    set status(value) {
        this._status = value;
    }


    get isMigrating() {
        return this._isMigrating;
    }

    get entityGroups() {
        return this._entityGroups;
    }

    get errors() {
        return this._errors;
    }

    subscribeStatus(cb) {
        this._statusSubscriber = cb;
    }

    unsubscribeStatus() {
        this._statusSubscriber = null;
    }

    subscribeProgress(cb) {
        this._progressSubscriber = cb;
    }

    unsubscribeProgress() {
        this._progressSubscriber = null;
    }

    async startMigration(profile, entityGroups, statusCallback, progressCallback) {
        return new Promise(async (resolve, reject) => {
            if (this._isMigrating) {
                reject();
                return;
            }

            // Wait for the 'migrationWanted' request and response to allow or deny the migration
            let isRunningInOtherTab = true;
            await this._isMigrationRunningInOtherTab().then((isRunning) => {
                isRunningInOtherTab = isRunning;
            });
            if (isRunningInOtherTab) {
                reject();
                return;
            }

            this._isMigrating = true;
            this._profile = profile;
            this._entityGroups = entityGroups;
            this._errors = [];
            this.subscribeStatus(statusCallback);
            this.subscribeProgress(progressCallback);

            // step 1 - read/fetch
            await this._fetchData();

            // step 2- write
            await this._writeData();

            // step 3 - media download
            await this._downloadData();

            // step 4 - show results
            await this._migrateFinish();

            this._isMigrating = false;
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

    _callProgressSubscriber(param) {
        if (this._progressSubscriber !== null) {
            this._progressSubscriber.call(null, param);
        }
    }

    _callStatusSubscriber(param) {
        if (this._statusSubscriber !== null) {
            this._statusSubscriber.call(null, param);
        }
    }

    _fetchData() {
        this._resetProgress();
        this._status = this.MIGRATION_STATUS.FETCH_DATA;
        this._callStatusSubscriber({ status: this.status });
        return this._migrateProcess('fetchData');
    }

    _writeData() {
        this._resetProgress();
        this._status = this.MIGRATION_STATUS.WRITE_DATA;
        this._callStatusSubscriber({ status: this.status });
        return this._migrateProcess('writeData');
    }

    _downloadData() {
        this._resetProgress();
        this._status = this.MIGRATION_STATUS.DOWNLOAD_DATA;
        this._callStatusSubscriber({ status: this.status });
    }

    _migrateFinish() {
        this._resetProgress();
        this._status = this.MIGRATION_STATUS.FINISHED;
        this._callStatusSubscriber({ status: this.status });
    }

    _resetProgress() {
        this._entityGroups.forEach((group) => {
            group.progress = 0;
        });
    }

    /**
     * Do all the API requests for all entities with the given methodName
     *
     * @param methodName api endpoint name for example 'fetchData' or 'writeData'
     * @returns {Promise}
     * @private
     */
    async _migrateProcess(methodName) {
        return new Promise(async (resolve) => {
            for (let i = 0; i < this._entityGroups.length; i += 1) {
                let groupProgress = 0;
                for (let ii = 0; ii < this._entityGroups[i].entities.length; ii += 1) {
                    const entityName = this._entityGroups[i].entities[ii].entityName;
                    const entityCount = this._entityGroups[i].entities[ii].entityCount;
                    await this._migrateEntity(entityName, entityCount, this._entityGroups[i], groupProgress, methodName);
                    groupProgress += entityCount;
                }
            }

            resolve();
        });
    }

    /**
     * Do all the API requests for one entity in chunks
     *
     * @param entityName
     * @param entityCount
     * @param group
     * @param groupProgress
     * @param methodName
     * @returns {Promise<void>}
     * @private
     */
    async _migrateEntity(entityName, entityCount, group, groupProgress, methodName) {
        let currentOffset = 0;
        while (currentOffset < entityCount) {
            await this._migrateEntityRequest(entityName, methodName, currentOffset);
            let newOffset = currentOffset + this._chunkSize;
            if (newOffset > entityCount) {
                newOffset = entityCount;
            }

            // update own state of progress
            group.progress = groupProgress + newOffset;

            // call event subscriber
            this._callProgressSubscriber({
                entityName,
                entityGroupProgressValue: groupProgress + newOffset
            });

            currentOffset += this._chunkSize;
        }

        this._chunkSize = this._DEFAULT_CHUNK_SIZE;
    }

    /**
     * Do a single API request for the given entity with given offset.
     *
     * @param entityName
     * @param methodName
     * @param offset
     * @returns {Promise}
     * @private
     */
    _migrateEntityRequest(entityName, methodName, offset) {
        const params = {
            profile: this._profile.profile,
            gateway: this._profile.gateway,
            credentialFields: this._profile.credentialFields,
            entity: entityName,
            offset: offset,
            limit: this._chunkSize
        };

        return new Promise((resolve) => {
            const beforeRequestTime = new Date();
            this._migrationService[methodName](params).then(() => {
                const afterRequestTime = new Date();
                this._handleChunkSize(afterRequestTime.getTime() - beforeRequestTime.getTime());
                resolve();
            }).catch((response) => {
                if (response.response.data && response.response.data.errors) {
                    response.response.data.errors.forEach((error) => {
                        this._addError(error);
                    });
                }

                const afterRequestTime = new Date();
                this._handleChunkSize(afterRequestTime.getTime() - beforeRequestTime.getTime());
                resolve();
            });
        });
    }

    /**
     * Update the chunkSize depending on the requestTime
     *
     * @param requestTime
     * @private
     */
    _handleChunkSize(requestTime) {
        if (requestTime < this._MAX_REQUEST_TIME) {
            this._chunkSize += this._CHUNK_INCREMENT;
        }

        if (requestTime > this._MAX_REQUEST_TIME) {
            this._chunkSize -= this._CHUNK_INCREMENT;
        }
    }

    _addError(error) {
        this._errors.push(error);
    }
}

export default MigrationService;
