import CriteriaFactory from 'src/core/factory/criteria.factory';

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
     * @param {MigrationRunService} migrationRunService
     * @param {MigrationDataService} migrationDataService
     * @param {MigrationMediaFileService} migrationMediaFileService
     */
    constructor(migrationRunService, migrationDataService, migrationMediaFileService) {
        this._migrationRunService = migrationRunService;
        this._migrationDataService = migrationDataService;
        this._migrationMediaFileService = migrationMediaFileService;
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
            // only for write
            if (status === MIGRATION_STATUS.WRITE_DATA) {
                this._updateEntityCountForWrite(entityGroups, runId).then((newEntityGroups) => {
                    this._migrationRunService.getById(runId).then((response) => {
                        const totals = response.data.totals;
                        const toBeWritten = {};
                        entityGroups.forEach((entityGroup) => {
                            entityGroup.entities.forEach((entity) => {
                                toBeWritten[entity.entityName] = entity.entityCount;
                            });
                        });
                        totals.toBeWritten = toBeWritten;

                        this._migrationRunService.updateById(runId, { totals: totals }).then(() => {
                            resolve([newEntityGroups]);
                        });
                    });
                });
            } else if (status === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                this._getAssetTotalCount(runId).then((assetTotalCount) => {
                    resolve([entityGroups, assetTotalCount]);
                });
            } else {
                resolve([entityGroups]);
            }
        });
    }

    /**
     * Count fetched data and set the new entity count.
     * It's necessary to do this, because of unconverted data.
     *
     * @param {Array} entityGroups
     * @param {string} runId
     * @returns {Promise}
     * @private
     */
    _updateEntityCountForWrite(entityGroups, runId) {
        return new Promise((resolve) => {
            const count = [
                {
                    name: 'entityCount',
                    type: 'value_count',
                    field: 'swag_migration_data.entity'
                }
            ];
            const criteria = CriteriaFactory.multi(
                'AND',
                CriteriaFactory.equals('runId', runId),
                CriteriaFactory.equals('convertFailure', false),
                CriteriaFactory.not(
                    'AND',
                    CriteriaFactory.equals('converted', null)
                )
            );
            const params = {
                aggregations: count,
                criteria: criteria,
                limit: 1
            };

            this._migrationDataService.getList(params).then((response) => {
                const entityCount = response.aggregations.entityCount;
                entityGroups.forEach((entityGroup) => {
                    let groupsCount = 0;
                    entityGroup.entities.forEach((entity) => {
                        entityCount.forEach((countedEntity) => {
                            if (entity.entityName === countedEntity.key) {
                                entity.entityCount = parseInt(countedEntity.count, 10);
                            }
                        });
                        groupsCount += entity.entityCount;
                    });
                    entityGroup.count = groupsCount;
                });

                resolve(entityGroups);
            });
        });
    }

    /**
     * Get the count of media objects that are available for the migration.
     *
     * @param {string} runId
     * @returns {Promise}
     * @private
     */
    _getAssetTotalCount(runId) {
        return new Promise((resolve) => {
            const count = [
                {
                    name: 'mediaCount',
                    type: 'count',
                    field: 'swag_migration_media_file.mediaId'
                }
            ];
            const criteria = CriteriaFactory.multi(
                'AND',
                CriteriaFactory.equals('runId', runId),
                CriteriaFactory.equals('written', true),
                CriteriaFactory.equals('downloaded', false)
            );
            const params = {
                aggregations: count,
                criteria: criteria,
                limit: 1
            };

            this._migrationMediaFileService.getList(params).then((res) => {
                resolve(parseInt(res.aggregations.mediaCount.count, 10));
            }).catch(() => {
                resolve(0);
            });
        });
    }
}

export default {
    WorkerStatusManager,
    MIGRATION_STATUS
};
