export class WorkerDownload {
    /**
     * @param {number} status
     * @param {Object} downloadParams
     * @param {WorkerStatusManager} workerStatusManager
     * @param {MigrationApiService} migrationService
     * @param {function} onProgressCB
     * @param {function} onErrorCB
     */
    constructor(
        status,
        downloadParams,
        workerStatusManager,
        migrationService,
        onProgressCB,
        onErrorCB
    ) {
        this._MAX_REQUEST_TIME_IN_MILLISECONDS = 10000;
        this._ASSET_UUID_CHUNK = 100; // Amount of uuids we fetch with one request
        this._ASSET_WORKLOAD_COUNT = 5; // The amount of assets we download per request in parallel
        // The maximum amount of bytes we download per file in one request
        this._ASSET_FILE_CHUNK_BYTE_SIZE = 1000 * 1000 * 8; // 8 MB
        this._CHUNK_SIZE_BYTE_INCREMENT = 250 * 1000; // 250 KB
        this._ASSET_MIN_FILE_CHUNK_BYTE_SIZE = this._CHUNK_SIZE_BYTE_INCREMENT;

        this._runId = downloadParams.runUuid;
        this._status = status;
        this._downloadParams = downloadParams;
        this._workerStatusManager = workerStatusManager;
        this._migrationService = migrationService;

        this._interrupt = false;
        this._assetTotalCount = 0;
        this._assetUuidPool = [];
        this._assetWorkload = [];
        this._assetProgress = 0;

        // callbacks
        this._onProgressCB = onProgressCB;
        this._onErrorCB = onErrorCB;
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
    set onProgressCB(value) {
        this._onProgressCB = value;
    }

    /**
     * @param {function} value
     */
    set onErrorCB(value) {
        this._onErrorCB = value;
    }

    /**
     * @param {Object} param
     * @private
     */
    _callProgressCB(param) {
        if (this._onProgressCB !== null) {
            this._onProgressCB(param);
        }
    }

    /**
     * @param {Object} param
     * @private
     */
    _callErrorCB(param) {
        if (this._onErrorCB !== null) {
            this._onErrorCB(param);
        }
    }

    /**
     * Do all the API requests for all entities with the given methodName
     *
     * @param {Object} entityGroups
     * @param {number} groupStartIndex
     * @param {number} entityStartIndex
     * @param {number} entityOffset
     * @returns {Promise}
     */
    /* eslint-disable no-unused-vars, no-await-in-loop */
    async migrateProcess(entityGroups, groupStartIndex = 0, entityStartIndex = 0, entityOffset = 0) {
        return new Promise(async (resolve) => {
            await this._workerStatusManager.onStatusChanged(
                this._runId,
                entityGroups,
                this._status
            ).then(([newEntityGroups, assetTotalCount]) => {
                entityGroups = newEntityGroups;
                this._assetTotalCount = assetTotalCount;
            });

            if (entityOffset === 0) {
                this._resetAssetProgress();
            } else {
                this._assetTotalCount += entityOffset; // we need to add the downloaded / finished count
                this._assetProgress += entityOffset;
                this._assetUuidPool = [];
                this._assetWorkload = [];
            }
            this._downloadProcess().then(() => {
                resolve();
            });
        });
    }
    /* eslint-enable no-unused-vars, no-await-in-loop */

    _resetAssetProgress() {
        this._assetUuidPool = [];
        this._assetWorkload = [];
        this._assetProgress = 0;
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
                if (this._interrupt) {
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
     * @param {number} assetCount the amount of uuids to add
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
     * @param {Array<Object>} newWorkload
     * @param {number} requestTime
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
        this._callProgressCB({
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
     * @param {number} requestTime Request time in milliseconds
     * @private
     */
    _handleAssetFileChunkByteSize(requestTime) {
        if (requestTime < this._MAX_REQUEST_TIME_IN_MILLISECONDS) {
            this._ASSET_FILE_CHUNK_BYTE_SIZE += this._CHUNK_SIZE_BYTE_INCREMENT;
        }

        if (
            requestTime > this._MAX_REQUEST_TIME_IN_MILLISECONDS &&
            (this._ASSET_FILE_CHUNK_BYTE_SIZE - this._CHUNK_SIZE_BYTE_INCREMENT) >= this._ASSET_MIN_FILE_CHUNK_BYTE_SIZE
        ) {
            this._ASSET_FILE_CHUNK_BYTE_SIZE -= this._CHUNK_SIZE_BYTE_INCREMENT;
        }
    }
}

export default WorkerDownload;
