import template from './swag-migration-data-selector.html.twig';
import './swag-migration-data-selector.scss';
import type { MigrationDataSelection } from '../../../../type/types';
import type { MigrationStore } from '../../store/migration.store';
import { MIGRATION_STORE_ID } from '../../store/migration.store';

const { Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

/**
 * @private
 */
export interface SwagMigrationDataSelectorData {
    migrationStore: MigrationStore;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    data(): SwagMigrationDataSelectorData {
        return {
            migrationStore: Shopware.Store.get(MIGRATION_STORE_ID),
        };
    },

    computed: {
        ...mapState(
            () => Store.get(MIGRATION_STORE_ID),
            [
                'environmentInformation',
                'dataSelectionTableData',
                'dataSelectionIds',
            ],
        ),

        displayWarnings() {
            return this.environmentInformation?.displayWarnings;
        },
    },

    methods: {
        tableDataGridMounted() {
            this.dataSelectionIds.forEach((id: string) => {
                this.$refs.tableDataGrid?.selectItem(true, { id });
            });
        },

        onGridSelectItem(selection: Record<string, MigrationDataSelection>) {
            const selectionIds = Object.keys(selection);

            this.dataSelectionTableData.forEach((data: MigrationDataSelection) => {
                if (data.requiredSelection !== true) {
                    return;
                }

                if (!selectionIds.includes(data.id)) {
                    selectionIds.push(data.id);

                    this.$nextTick(() => {
                        this.$refs.tableDataGrid.selectItem(true, data);
                    });
                }
            });

            this.migrationStore.setDataSelectionIds(selectionIds);
        },

        showHelptext(entityTotals: number[]) {
            return entityTotals !== undefined && Object.keys(entityTotals).length > 1;
        },

        getHelptext(item: MigrationDataSelection) {
            if (item.entityTotals === undefined || Object.keys(item.entityTotals).length === 0) {
                return '';
            }

            let string = '';
            Object.keys(item.entityTotals).forEach((key) => {
                string += `${this.$tc(item.entityNames[key])}: ${item.entityTotals[key]}</br>`;
            });

            return string;
        },
    },
});
