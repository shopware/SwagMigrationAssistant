const { State } = Shopware;

class UiStoreInitService {
    constructor(migrationService) {
        this._migrationService = migrationService;
        this._migrationProcessState = State.get('swagMigration/process');
    }

    initUiStore() {
        return new Promise((resolve, reject) => {
            const connectionId = this._migrationProcessState.connectionId;

            if (connectionId === undefined) {
                resolve();
                return;
            }

            this._migrationService.getDataSelection(connectionId).then((dataSelection) => {
                State.commit('swagMigration/ui/setPremapping', []);
                State.commit('swagMigration/ui/setDataSelectionTableData', dataSelection);
                const selectedIds = dataSelection.filter(selection => selection.requiredSelection)
                    .map(selection => selection.id);
                State.commit('swagMigration/ui/setDataSelectionIds', selectedIds);
                resolve();
            }).catch(() => {
                reject();
            });
        });
    }
}

export default UiStoreInitService;
