const { State } = Shopware;

class ProcessStoreInitService {
    constructor(migrationService) {
        this._migrationService = migrationService;
        this._migrationProcessStore = State.getStore('migrationProcess');
        this._migrationGeneralSettingStore = State.getStore('swag_migration_general_setting');
    }

    initProcessStore() {
        return new Promise((resolve, reject) => {
            this._migrationProcessStore.setEntityGroups([]);
            this._migrationProcessStore.setEnvironmentInformation({});
            this._migrationGeneralSettingStore.getList({ limit: 1 }).then((settings) => {
                if (!settings || settings.items.length === 0) {
                    reject();
                    return null;
                }

                const connectionId = settings.items[0].selectedConnectionId;

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
