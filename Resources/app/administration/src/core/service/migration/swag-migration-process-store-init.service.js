const { State } = Shopware;
const { Criteria } = Shopware.Data;

/**
 * @private
 * @package services-settings
 */
class ProcessStoreInitService {
    constructor(migrationApiService, repositoryFactory, context) {
        this._migrationApiService = migrationApiService;
        this._migrationGeneralSettingRepository = repositoryFactory.create('swag_migration_general_setting');
        this._context = context;
    }

    initProcessStore() {
        return new Promise((resolve, reject) => {
            State.commit('swagMigration/process/setEnvironmentInformation', {});
            const criteria = new Criteria(1, 1);

            this._migrationGeneralSettingRepository.search(criteria, this._context).then((settings) => {
                if (settings.length === 0) {
                    reject();
                    return null;
                }

                const connectionId = settings.first().selectedConnectionId;

                if (connectionId === null) {
                    reject();
                    return null;
                }

                State.commit('swagMigration/process/setConnectionId', connectionId);
                return connectionId;
            }).then((connectionId) => {
                if (connectionId === null) {
                    reject();
                    return;
                }

                this._migrationApiService.checkConnection(connectionId)
                    .then((connectionCheckResponse) => {
                        State.commit('swagMigration/process/setEnvironmentInformation', connectionCheckResponse);
                        resolve();
                    }).catch(() => {
                        reject();
                    });
            }).catch(() => {
                reject();
            });
        });
    }
}

export default ProcessStoreInitService;
