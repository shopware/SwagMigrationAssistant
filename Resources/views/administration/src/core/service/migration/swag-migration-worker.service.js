
class MigrationService {
    constructor(migrationService) {
        this._MAX_REQUEST_TIME = 2000;   //in ms
        this._DEFAULT_CHUNK_SIZE = 50;   //in data sets
        this._CHUNK_INCREMENT = 5;       //in data sets

        this.MIGRATION_STATUS = {
            WAITING: -1,
            FETCH_DATA: 0,
            WRITE_DATA: 1,
            DOWNLOAD_DATA: 2,
            FINISHED: 3,
        };

        this._migrationService = migrationService;
        this._errors = [];
        this._chunkSize = this._DEFAULT_CHUNK_SIZE;

        //state variables
        this._isMigrating = false;
        this._entityGroups = [];
        this._progressSubscriber = null;
        this._statusSubscriber = null;
        this._profile = null;
        this._status = null;
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
            if (this._isMigrating)
                reject();

            this._isMigrating = true;
            this._profile = profile;
            this._entityGroups = entityGroups;
            this._errors = [];
            this.subscribeStatus(statusCallback);
            this.subscribeProgress(progressCallback);

            //step 1 - read/fetch
            await this._fetchData();

            //step 2- write
            await this._writeData();

            //step 3 - media download
            await this._downloadData();

            //step 4 - show results
            await this._migrateFinish();

            this._isMigrating = false;
        });
    }

    _callProgressSubscriber(param) {
        if (this._progressSubscriber !== null) {
            this._progressSubscriber(param);
        }
    }

    _callStatusSubscriber(param) {
        if (this._statusSubscriber !== null) {
            this._statusSubscriber(param);
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

    async _migrateProcess(methodName) {
        return new Promise(async (resolve, reject) => {
            for (let i = 0; i < this._entityGroups.length; i++) {
                let groupProgress = 0;
                for (let ii = 0; ii < this._entityGroups[i].entities.length; ii++) {
                    let entityName = this._entityGroups[i].entities[ii].entityName;
                    let entityCount = this._entityGroups[i].entities[ii].entityCount;
                    await this._migrateEntity(entityName, entityCount, this._entityGroups[i], groupProgress, methodName);
                    groupProgress += entityCount;
                }
            }

            resolve();
        });
    }

    async _migrateEntity(entityName, entityCount, group, groupProgress, methodName) {
        let currentOffset = 0;
        while (currentOffset < entityCount) {
            await this._migrateEntityRequest(entityName, methodName, currentOffset);
            let newOffset = currentOffset + this._chunkSize;
            if (newOffset > entityCount) {
                newOffset = entityCount;
            }

            //update own state of progress
            group.progress = groupProgress + newOffset;

            //call event subscriber
            this._callProgressSubscriber({
                entityName,
                entityGroupProgressValue: groupProgress + newOffset
            });

            currentOffset += this._chunkSize;
        }

        this._chunkSize = this._DEFAULT_CHUNK_SIZE;
    }

    _handleChunkSize(requestTime) {
        if (requestTime < this._MAX_REQUEST_TIME) {
            this._chunkSize += this._CHUNK_INCREMENT;
        }

        if (requestTime > this._MAX_REQUEST_TIME) {
            this._chunkSize -= this._CHUNK_INCREMENT;
        }
    }

    _migrateEntityRequest(entityName, methodName, offset) {
        let params = {
            profile: this._profile.profile,
            gateway: this._profile.gateway,
            credentialFields: this._profile.credentialFields,
            entity: entityName,
            offset: offset,
            limit: this._chunkSize
        };

        return new Promise((resolve, reject) => {
            let beforeRequestTime = new Date();
            this._migrationService[methodName](params).then((response) => {

                let afterRequestTime = new Date();
                this._handleChunkSize(afterRequestTime.getTime() -beforeRequestTime.getTime());

                resolve();
            }).catch((response) => {
                if (response.response.data && response.response.data.errors) {
                    response.response.data.errors.forEach((error) => {
                        this._addError(error);
                    });
                }

                let afterRequestTime = new Date();
                this._handleChunkSize(afterRequestTime.getTime() -beforeRequestTime.getTime());
                //reject();
                resolve();
            });
        });
    }

    _addError(error) {
        this._errors.push(error);
    }
}

export default MigrationService;
