import { State } from 'src/core/shopware';

/**
 * Describes the current step in the migration (status).
 *
 * @type {Readonly<{
 *                  WAITING: number,
 *                  PREMAPPING:number,
 *                  FETCH_DATA: number,
 *                  WRITE_DATA: number,
 *                  PROCESS_MEDIA_FILES: number,
 *                  FINISHED: number
*                  }>}
 */
export const MIGRATION_STATUS = Object.freeze({
    WAITING: -1,
    PREMAPPING: 0,
    FETCH_DATA: 1,
    WRITE_DATA: 2,
    PROCESS_MEDIA_FILES: 3,
    FINISHED: 4
});

const MIGRATION_DISPLAY_STATUS = {};
Object.keys(MIGRATION_STATUS).forEach((key) => {
    if (!(key === 'WAITING' || key === 'PREMAPPING' || key === 'FINISHED')) {
        MIGRATION_DISPLAY_STATUS[MIGRATION_STATUS[key]] = key;
    }
});


export { MIGRATION_DISPLAY_STATUS };


export class WorkerStatusManager {
    /**
     * @param {MigrationApiService} migrationService
     */
    constructor(migrationService) {
        this._migrationService = migrationService;
        this._migrationProcessStore = State.getStore('migrationProcess');
    }

    /**
     * This handles the necessary things before we start working on the status.
     * For example it resets the progress and updates the counts before the 'WRITE_DATA' operation.
     *
     * @param {string} runId
     * @returns {Promise}
     */
    onStatusChanged(runId) {
        if (this._migrationProcessStore.state.statusIndex === MIGRATION_STATUS.WRITE_DATA) {
            return this.beforeWriteProgress(runId);
        }

        if (this._migrationProcessStore.state.statusIndex === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
            return this.beforeProcessMedia(runId);
        }

        if (this._migrationProcessStore.state.statusIndex === MIGRATION_STATUS.FINISHED) {
            return this.onFinish(runId);
        }

        return Promise.resolve();
    }

    beforeWriteProgress(runId) {
        return new Promise(async (resolve, reject) => {
            let requestRetry = true;
            let requestFailedCount = 0;
            /* eslint-disable no-await-in-loop, no-loop-func */
            while (requestRetry) {
                await this._migrationService.updateWriteProgress(runId).then((response) => {
                    response = response.filter((group) => {
                        return group.id !== 'processMediaFiles';
                    });
                    this._migrationProcessStore.setEntityGroups(response);
                    requestRetry = false;
                }).catch(() => {
                    requestFailedCount += 1;
                });

                if (requestFailedCount >= 3) {
                    requestRetry = false;
                    reject();
                    return;
                }
            }
            /* eslint-enable no-await-in-loop, no-loop-func */

            resolve();
        });
    }

    beforeProcessMedia(runId) {
        return new Promise(async (resolve, reject) => {
            let requestRetry = true;
            let requestFailedCount = 0;
            /* eslint-disable no-await-in-loop, no-loop-func */
            while (requestRetry) {
                await this._migrationService.updateMediaFilesProgress(runId).then((response) => {
                    this._migrationProcessStore.setEntityGroups(response);
                    requestRetry = false;
                }).catch(() => {
                    requestFailedCount += 1;
                });

                if (requestFailedCount >= 3) {
                    requestRetry = false;
                    reject();
                    return;
                }
            }
            /* eslint-enable no-await-in-loop, no-loop-func */

            resolve();
        });
    }

    onFinish(runId) {
        return new Promise(async (resolve, reject) => {
            let requestRetry = true;
            let requestFailedCount = 0;
            /* eslint-disable no-await-in-loop, no-loop-func */
            while (requestRetry) {
                await this._migrationService.finishMigration(runId).then(() => {
                    requestRetry = false;
                }).catch(() => {
                    requestFailedCount += 1;
                });

                if (requestFailedCount >= 3) {
                    requestRetry = false;
                    reject();
                    return;
                }
            }
            /* eslint-enable no-await-in-loop, no-loop-func */

            resolve();
        });
    }
}

export default {
    WorkerStatusManager,
    MIGRATION_STATUS
};
