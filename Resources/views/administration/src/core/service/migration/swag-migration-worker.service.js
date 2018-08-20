
class MigrationService {


    constructor(migrationService) {
        this.MAX_REQUEST_TIME = 2000;   //in ms
        this.DEFAULT_CHUNK_SIZE = 50;   //in data sets
        this.CHUNK_INCREMENT = 5;       //in data sets

        this.migrationService = migrationService;
        this.errors = [];
        this.chunkSize = this.DEFAULT_CHUNK_SIZE;
    }

    async fetchData(profile, entityNames, entityCounts, progressCallback) {
        return new Promise(async (resolve, reject) => {
            for (let i = 0; i < entityNames.length; i++) {
                let entityName = entityNames[i];
                let entityCount = entityCounts[entityName];
                await this.migrateEntity(profile, entityName, entityCount, 'fetchData', progressCallback);
            }

            resolve();
        });
    }

    async writeData(profile, entityNames, entityCounts, progressCallback) {
        return new Promise(async (resolve, reject) => {
            for (let i = 0; i < entityNames.length; i++) {
                let entityName = entityNames[i];
                let entityCount = entityCounts[entityName];
                await this.migrateEntity(profile, entityName, entityCount, 'writeData', progressCallback);
            }

            resolve();
        });
    }

    async migrateEntity(profile, entityName, entityCount, methodName, progressCallback) {
        let currentOffset = 0;

        while (currentOffset < entityCount) {
            await this.migrateEntityRequest(profile, entityName, methodName, currentOffset);
            let newOffset = currentOffset + this.chunkSize;
            if (newOffset > entityCount) {
                newOffset = entityCount;
            }

            progressCallback({
                entityName,
                newOffset,
                deltaOffset: newOffset - currentOffset,
                entityCount,
            });

            currentOffset += this.chunkSize;
        }

        this.chunkSize = this.DEFAULT_CHUNK_SIZE;
    }

    handleChunkSize(requestTime) {
        if (requestTime < this.MAX_REQUEST_TIME) {
            this.chunkSize += this.CHUNK_INCREMENT;
        }

        if (requestTime > this.MAX_REQUEST_TIME) {
            this.chunkSize -= this.CHUNK_INCREMENT;
        }
    }

    migrateEntityRequest(profile, entityName, methodName, offset) {
        let params = {
            profile: profile.profile,
            gateway: profile.gateway,
            credentialFields: profile.credentialFields,
            entity: entityName,
            offset: offset,
            limit: this.chunkSize
        };

        return new Promise((resolve, reject) => {
            let beforeRequestTime = new Date();
            this.migrationService[methodName](params).then((response) => {

                let afterRequestTime = new Date();
                this.handleChunkSize(afterRequestTime.getTime() -beforeRequestTime.getTime());

                resolve();
            }).catch((response) => {
                if (response.response.data && response.response.data.errors) {
                    response.response.data.errors.forEach((error) => {
                        this.addError(error);
                    });
                }

                let afterRequestTime = new Date();
                this.handleChunkSize(afterRequestTime.getTime() -beforeRequestTime.getTime());
                //reject();
                resolve();
            });
        });
    }

    addError(error) {
        this.errors.push(error);
    }
}

export default MigrationService;
