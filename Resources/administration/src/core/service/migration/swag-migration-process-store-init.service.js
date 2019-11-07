const { StateDeprecated } = Shopware;
const { Criteria } = Shopware.Data;

class ProcessStoreInitService {
    constructor(migrationService, repositoryFactory, context) {
        this._migrationService = migrationService;
        this._migrationProcessStore = StateDeprecated.getStore('migrationProcess');
        this._migrationGeneralSettingRepository = repositoryFactory.create('swag_migration_general_setting');
        this._context = context;
    }

    initProcessStore() {
        return new Promise((resolve, reject) => {
            this._migrationProcessStore.setEntityGroups([]);
            this._migrationProcessStore.setEnvironmentInformation({});
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

                this._migrationProcessStore.setConnectionId(connectionId);
                return connectionId;
            }).then((connectionId) => {
                if (connectionId === null) {
                    reject();
                    return;
                }

                this._migrationService.checkConnection(this._migrationProcessStore.state.connectionId)
                    .then((connectionCheckResponse) => {
                        this._migrationProcessStore.setEnvironmentInformation(connectionCheckResponse);
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
