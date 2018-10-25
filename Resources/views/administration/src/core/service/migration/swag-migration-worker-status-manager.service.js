import CriteriaFactory from 'src/core/factory/criteria.factory';

/**
 * Describes the current step in the migration (status).
 *
 * @type {Readonly<{WAITING: number, FETCH_DATA: number, WRITE_DATA: number, DOWNLOAD_DATA: number, FINISHED: number}>}
 */
export const MIGRATION_STATUS = Object.freeze({
    WAITING: -1,
    FETCH_DATA: 0,
    WRITE_DATA: 1,
    DOWNLOAD_DATA: 2,
    FINISHED: 3
});

export class WorkerStatusManager {
    constructor(migrationRunService, migrationDataService) {
        this._migrationRunService = migrationRunService;
        this._migrationDataService = migrationDataService;
    }

    /**
     * This handles the nessecary things before we start working on the status.
     * For example it resets the progress and updates the counts before the 'WRITE_DATA' operation.
     *
     * @param {string} runId
     * @param {Array} entityGroups
     * @param {int} status MIGRATION_STATUS
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
                            resolve(newEntityGroups);
                        });
                    });
                });
                return;
            }

            resolve(entityGroups);
        });
    }

    /**
     * Set progress to 0 for every group.
     *
     * @param {Array} entityGroups
     * @returns {Array}
     */
    static resetProgress(entityGroups) {
        entityGroups.forEach((group) => {
            group.progress = 0;
        });

        return entityGroups;
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
            const count = {
                entityCount: {
                    value_count: { field: 'swag_migration_data.entity' }
                }
            };
            const criteria = CriteriaFactory.nested(
                'AND',
                CriteriaFactory.term('runId', runId),
                CriteriaFactory.not(
                    'AND',
                    CriteriaFactory.term('converted', null)
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
}

export default {
    WorkerStatusManager,
    MIGRATION_STATUS
};
