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
                this._migrationUiStore.setDataSelectionTableData(this._filterTableData(dataSelection));
                resolve();
            });
        });
    }

    _filterTableData(dataSelection) {
        if (this._migrationProcessStore.state.environmentInformation === null) {
            return [];
        }

        const filtered = [];
        dataSelection.forEach((group) => {
            let containsData = false;
            group.total = 0;
            group.entityTotals = {};
            group.entityNames.forEach((name) => {
                if (this._migrationProcessStore.state.environmentInformation.totals[name] > 0) {
                    const entityTotal = this._migrationProcessStore.state.environmentInformation.totals[name];
                    containsData = true;
                    group.entityTotals[name] = entityTotal;
                    group.total += entityTotal;
                }
            });

            if (containsData) {
                filtered.push(group);
            }
        });

        return filtered;
    }
}

export default UiStoreInitService;
