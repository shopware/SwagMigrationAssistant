class MigrationIndexingWorker {
    /**
     * @param {MigrationIndexingApiService} indexingApiService
     */
    constructor(indexingApiService) {
        this._indexingApiService = indexingApiService;
    }

    start() {
        return new Promise(async (resolve) => {
            let running = true;
            let lastResult = {};
            while (running) {
                // eslint-disable-next-line no-await-in-loop
                await this._indexingApiService.indexing(
                    lastResult.lastIndexer,
                    lastResult.offset,
                    lastResult.timestamp,
                    // eslint-disable-next-line no-loop-func
                ).then((result) => {
                    if (result.done !== undefined && result.done === true) {
                        running = false;
                        return;
                    }

                    lastResult = result;
                });
            }
            resolve();
        });
    }
}

export default MigrationIndexingWorker;
