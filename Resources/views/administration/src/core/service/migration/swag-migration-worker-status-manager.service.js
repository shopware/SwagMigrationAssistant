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
        return new Promise((resolve) => {
            if (this._migrationProcessStore.state.statusIndex === MIGRATION_STATUS.WRITE_DATA) {
                this._migrationService.updateWriteProgress(runId).then((response) => {
                    response = response.filter((group) => {
                        return group.id !== 'processMediaFiles';
                    });
                    this._migrationProcessStore.setEntityGroups(response);
                    resolve();
                });
                return;
            }

            if (this._migrationProcessStore.state.statusIndex === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                this._migrationService.updateMediaFilesProgress(runId).then((response) => {
                    this._migrationProcessStore.setEntityGroups(response);
                    resolve();
                });
                return;
            }

            resolve();
        });
    }
}

export default {
    WorkerStatusManager,
    MIGRATION_STATUS
};
