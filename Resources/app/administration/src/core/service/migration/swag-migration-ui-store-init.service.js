const { StateDeprecated } = Shopware;

class UiStoreInitService {
    constructor(migrationService) {
        this._migrationService = migrationService;
        this._migrationProcessStore = StateDeprecated.getStore('migrationProcess');
        this._migrationUiStore = StateDeprecated.getStore('migrationUI');
    }

    initUiStore() {
        return new Promise((resolve, reject) => {
            const connectionId = this._migrationProcessStore.state.connectionId;

            if (connectionId === undefined) {
                resolve();
                return;
            }

            this._migrationService.getDataSelection(connectionId).then((dataSelection) => {
                this._migrationUiStore.setPremapping([]);
                this._migrationUiStore.setDataSelectionTableData(dataSelection);
                const selectedIds = dataSelection.filter(selection => selection.requiredSelection)
                    .map(selection => selection.id);
                this._migrationUiStore.setDataSelectionIds(selectedIds);
                resolve();
            }).catch(() => {
                reject();
            });
        });
    }
}

export default UiStoreInitService;
