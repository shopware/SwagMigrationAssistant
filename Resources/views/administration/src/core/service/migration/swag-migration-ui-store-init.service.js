import { State } from 'src/core/shopware';

class UiStoreInitService {
    constructor(migrationService) {
        this._migrationService = migrationService;
        this._migrationProcessStore = State.getStore('migrationProcess');
        this._migrationUiStore = State.getStore('migrationUI');
    }

    initUiStore() {
        return new Promise((resolve) => {
            const connectionId = this._migrationProcessStore.state.connectionId;

            if (connectionId === undefined) {
                resolve();
                return;
            }

            this._migrationService.getDataSelection(connectionId).then((dataSelection) => {
                this._migrationUiStore.setPremapping([]);
                this._migrationUiStore.setDataSelectionTableData(dataSelection);
                resolve();
            });
        });
    }
}

export default UiStoreInitService;
