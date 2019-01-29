/**
 * Describes the current step in the migration (status).
 *
 * @type {Readonly<{WAITING: number, FETCH_DATA: number, WRITE_DATA: number, PROCESS_MEDIA_FILES: number, FINISHED: number}>}
 */
export const MIGRATION_STATUS = Object.freeze({
    WAITING: -1,
    FETCH_DATA: 0,
    WRITE_DATA: 1,
    PROCESS_MEDIA_FILES: 2,
    FINISHED: 3
});

export class WorkerStatusManager {
    /**
     * @param {MigrationApiService} migrationService
     * @param {MigrationRunService} migrationRunService
     * @param {MigrationDataService} migrationDataService
     */
    constructor(migrationService, migrationRunService, migrationDataService) {
        this._migrationService = migrationService;
        this._migrationRunService = migrationRunService;
        this._migrationDataService = migrationDataService;
    }

    /**
     * This handles the necessary things before we start working on the status.
     * For example it resets the progress and updates the counts before the 'WRITE_DATA' operation.
     *
     * @param {string} runId
     * @param {Array} entityGroups
     * @param {number} status MIGRATION_STATUS
     * @returns {Promise}
     */
    onStatusChanged(runId, entityGroups, status) {
        return new Promise((resolve) => {
            if (status === MIGRATION_STATUS.WRITE_DATA) {
                this._migrationService.updateWriteProgress(runId).then((response) => {
                    resolve([response]);
                });
            } else if (status === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                this._migrationService.updateMediaFilesProgress(runId).then((response) => {
                    resolve([response]);
                });
            } else {
                resolve([entityGroups]);
            }
        });
    }
}

export default {
    WorkerStatusManager,
    MIGRATION_STATUS
};
