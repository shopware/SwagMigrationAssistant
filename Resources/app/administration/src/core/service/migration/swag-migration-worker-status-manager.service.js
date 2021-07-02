const { State } = Shopware;

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
    FINISHED: 4,
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
        this._migrationProcessState = State.get('swagMigration/process');
    }

    /**
     * This handles the necessary things before we start working on the new status.
     * For example it resets the progress and updates the counts before the 'WRITE_DATA' operation.
     *
     * @param {string} runId
     * @param {?number} newStatus
     * @returns {Promise}
     */
    changeStatus(runId, newStatus = null) {
        if (newStatus === null) {
            newStatus = this._migrationProcessState.statusIndex;
        }

        return new Promise((resolve, reject) => {
            if (newStatus === MIGRATION_STATUS.WRITE_DATA) {
                this.beforeWriteProgress(runId).then((...params) => {
                    this.onStatusPreparationFinished(newStatus);
                    resolve(...params);
                }).catch((err) => {
                    this.onStatusPreparationFinished(newStatus);
                    reject(err);
                });
                return;
            }

            if (newStatus === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                this.beforeProcessMedia(runId).then((...params) => {
                    this.onStatusPreparationFinished(newStatus);
                    resolve(...params);
                }).catch((err) => {
                    this.onStatusPreparationFinished(newStatus);
                    reject(err);
                });
                return;
            }

            if (newStatus === MIGRATION_STATUS.FINISHED) {
                this.onFinish(runId).then((...params) => {
                    this.onStatusPreparationFinished(newStatus);
                    resolve(...params);
                }).catch((err) => {
                    this.onStatusPreparationFinished(newStatus);
                    reject(err);
                });
                return;
            }

            this.onStatusPreparationFinished(newStatus);
            resolve();
        });
    }

    onStatusPreparationFinished(newStatus) {
        State.commit('swagMigration/process/setStatusIndex', newStatus);
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
                    State.commit('swagMigration/process/setEntityGroups', response);
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
                    const newEntityGroups = response.filter(group => group.id === 'processMediaFiles');
                    State.commit('swagMigration/process/setEntityGroups', newEntityGroups);
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
    MIGRATION_STATUS,
};
