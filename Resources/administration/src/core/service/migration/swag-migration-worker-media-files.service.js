import { State } from 'src/core/shopware';
import { WORKER_INTERRUPT_TYPE } from './swag-migration-worker.service';
import { WORKER_API_OPERATION } from './swag-migration-worker-request.service';

export class WorkerMediaFiles {
    /**
     * @param {Object} downloadParams
     * @param {WorkerStatusManager} workerStatusManager
     * @param {MigrationApiService} migrationService
     * @param {function} onInterruptCB
     */
    constructor(
        downloadParams,
        workerStatusManager,
        migrationService,
        onInterruptCB
    ) {
        this._MAX_REQUEST_TIME_IN_MILLISECONDS = 10000;
        this._MEDIA_UUID_CHUNK = 100; // Amount of uuids we fetch with one request
        this._MEDIA_WORKLOAD_COUNT = 5; // The amount of media we download per request in parallel
        // The maximum amount of bytes we download per file in one request
        this._MEDIA_FILE_CHUNK_BYTE_SIZE = 1000 * 1000 * 8; // 8 MB
        this._CHUNK_SIZE_BYTE_INCREMENT = 250 * 1000; // 250 KB
        this._MEDIA_MIN_FILE_CHUNK_BYTE_SIZE = this._CHUNK_SIZE_BYTE_INCREMENT;

        this._migrationProcessStore = State.getStore('migrationProcess');

        this._runId = downloadParams.runUuid;
        this._accessToken = downloadParams.swagMigrationAccessToken;
        this._downloadParams = downloadParams;
        this._workerStatusManager = workerStatusManager;
        this._migrationService = migrationService;

        this._interrupt = '';
        this._mediaTotalCount = 0;
        this._mediaUuidPool = [];
        this._mediaWorkload = [];
        this._mediaProgress = 0;

        // callbacks
        this._onInterruptCB = onInterruptCB;
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
    /* eslint-disable no-unused-vars, no-await-in-loop */
    async migrateProcess(groupStartIndex = 0, entityStartIndex = 0, entityOffset = 0) {
        return new Promise(async (resolve) => {
            let statusChangedError = false;
            await this._workerStatusManager.onStatusChanged(this._runId).then(() => {
                this._mediaTotalCount = 0;
                this._migrationProcessStore.state.entityGroups.forEach((group) => {
                    if (group.id === 'processMediaFiles') {
                        this._mediaTotalCount = group.total;
                    }
                });
            }).catch(() => {
                statusChangedError = true;
            });

            if (statusChangedError === true) {
                this.interrupt = WORKER_INTERRUPT_TYPE.PAUSE;
                this._callInterruptCB();
                resolve();
                return;
            }

            if (entityOffset === 0) {
                this._resetMediaProgress();
            } else {
                this._mediaProgress += entityOffset;
                this._mediaUuidPool = [];
                this._mediaWorkload = [];
            }
            this._processMediaFiles().then(() => {
                resolve();
            });
        });
    }
    /* eslint-enable no-unused-vars, no-await-in-loop */

    _resetMediaProgress() {
        this._mediaUuidPool = [];
        this._mediaWorkload = [];
        this._mediaProgress = 0;
    }

    /**
     * Get a chunk of media uuids and put it into our pool.
     *
     * @returns {Promise}
     * @private
     */
    _fetchMediaUuidsChunk() {
        return new Promise((resolve) => {
            if (this._mediaUuidPool.length >= this._MEDIA_WORKLOAD_COUNT) {
                resolve();
                return;
            }

            this._migrationService.fetchMediaUuids({
                runUuid: this._runId,
                limit: this._MEDIA_UUID_CHUNK
            }).then((res) => {
                res.mediaUuids.forEach((uuid) => {
                    let isInWorkload = false;
                    this._mediaWorkload.forEach((media) => {
                        if (media.uuid === uuid) {
                            isInWorkload = true;
                        }
                    });

                    if (!isInWorkload && !this._mediaUuidPool.includes(uuid)) {
                        this._mediaUuidPool.push(uuid);
                    }
                });
                resolve();
            }).catch(() => {
                this._migrationProcessStore.addError({
                    code: 'mediaProcessConnectionError',
                    internalError: true
                });
                resolve();
            });
        });
    }

    /**
     * Process all media files to filesystem
     *
     * @returns {Promise}
     * @private
     */
    async _processMediaFiles() {
        /* eslint-disable no-await-in-loop */
        return new Promise(async (resolve) => {
            await this._fetchMediaUuidsChunk();

            // make workload
            this._makeWorkload(this._MEDIA_WORKLOAD_COUNT);

            while (this._mediaProgress < this._mediaTotalCount) {
                // send workload to api
                let newWorkload;
                const beforeRequestTime = new Date();

                await this._processMediaFilesWorkload().then((w) => {
                    newWorkload = w;
                });

                if (this._interrupt !== '') {
                    this._callInterruptCB();
                    resolve();
                    return;
                }

                const afterRequestTime = new Date();
                // process response and update local workload
                this._updateWorkload(newWorkload, afterRequestTime - beforeRequestTime);

                await this._fetchMediaUuidsChunk();

                if (this._mediaUuidPool.length === 0 && newWorkload.length === 0) {
                    break;
                }
            }

            resolve();
        });
        /* eslint-enable no-await-in-loop */
    }

    /**
     * Push media uuids from the pool into the current workload
     *
     * @param {number} mediaCount the amount of uuids to add
     * @private
     */
    _makeWorkload(mediaCount) {
        const uuids = this._mediaUuidPool.splice(0, mediaCount);
        uuids.forEach((uuid) => {
            this._mediaWorkload.push({
                runId: this._runId,
                uuid,
                currentOffset: 0,
                state: 'inProgress'
            });
        });
    }

    /**
     * Analyse the given workload and update our own workload.
     * Remove finished media from our workload and add new ones.
     * Remove failed media (errorCount >= this._MEDIA_ERROR_THRESHOLD) and add errors for them.
     * Make sure we have the media amount in our workload that we specified (this._MEDIA_WORKLOAD_COUNT).
     *
     * @param {Object[]} newWorkload
     * @param {number} requestTime
     * @private
     */
    _updateWorkload(newWorkload, requestTime) {
        if (newWorkload.length === 0) {
            this._makeWorkload(this._MEDIA_WORKLOAD_COUNT);
            return;
        }

        const finishedMedia = newWorkload.filter((media) => media.state === 'finished');
        let mediaRemovedCount = finishedMedia.length;

        // check for errorCount
        newWorkload.forEach((media) => {
            if (media.state === 'error') {
                mediaRemovedCount += 1;
            }
        });

        this._mediaWorkload = newWorkload.filter((media) => media.state === 'inProgress');

        // Get the media that have utilized the full amount of fileByteChunkSize
        const mediaWithoutAnyErrors = this._mediaWorkload.filter((media) => !media.errorCount);
        if (mediaWithoutAnyErrors.length !== 0) {
            this._handleMediaFileChunkByteSize(requestTime);
        }

        this._mediaProgress += mediaRemovedCount;
        // call event subscriber
        this._migrationProcessStore.setEntityProgress(
            'media',
            this._mediaProgress,
            this._mediaTotalCount
        );

        this._makeWorkload(mediaRemovedCount);
    }

    /**
     * Send the media process request with our workload and fileChunkByteSize.
     *
     * @returns {Promise}
     * @private
     */
    _processMediaFilesWorkload() {
        return new Promise(async (resolve) => {
            let requestRetry = true;
            let requestFailedCount = 0;
            let responseWorkload = this._mediaWorkload;
            /* eslint-disable no-await-in-loop, no-loop-func */
            while (requestRetry) {
                await this._migrationService.processMedia({
                    runUuid: this._runId,
                    workload: this._mediaWorkload,
                    fileChunkByteSize: this._MEDIA_FILE_CHUNK_BYTE_SIZE,
                    swagMigrationAccessToken: this._accessToken
                }).then((res) => {
                    if (!res) {
                        requestFailedCount += 1;
                        return;
                    }

                    if (!res.validToken) {
                        this.interrupt = WORKER_INTERRUPT_TYPE.TAKEOVER;
                        requestRetry = false;
                        return;
                    }

                    responseWorkload = res.workload;
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

                    if (response.response.status === 500) {
                        // Don't retry if server errors happen, only if something is wrong with the connection.
                        requestRetry = false;
                        return;
                    }

                    this._migrationProcessStore.addError({
                        code: 'mediaProcessConnectionError',
                        internalError: true
                    });

                    requestFailedCount += 1;
                });

                if (requestFailedCount >= 3) {
                    requestRetry = false;
                    if (this.operation === WORKER_API_OPERATION[1]) {
                        this.interrupt = WORKER_INTERRUPT_TYPE.TAKEOVER; // TODO: check if we need a extra screen for this.
                    } else {
                        this.interrupt = WORKER_INTERRUPT_TYPE.PAUSE;
                    }
                }
            }
            /* eslint-enable no-await-in-loop, no-loop-func */

            resolve(responseWorkload);
        });
    }

    /**
     * Update the MEDIA_FILE_CHUNK_BYTE_SIZE depending on the requestTime
     *
     * @param {number} requestTime Request time in milliseconds
     * @private
     */
    _handleMediaFileChunkByteSize(requestTime) {
        if (requestTime < this._MAX_REQUEST_TIME_IN_MILLISECONDS) {
            this._MEDIA_FILE_CHUNK_BYTE_SIZE += this._CHUNK_SIZE_BYTE_INCREMENT;
        }

        if (
            requestTime > this._MAX_REQUEST_TIME_IN_MILLISECONDS &&
            (this._MEDIA_FILE_CHUNK_BYTE_SIZE - this._CHUNK_SIZE_BYTE_INCREMENT) >= this._MEDIA_MIN_FILE_CHUNK_BYTE_SIZE
        ) {
            this._MEDIA_FILE_CHUNK_BYTE_SIZE -= this._CHUNK_SIZE_BYTE_INCREMENT;
        }
    }
}

export default WorkerMediaFiles;
