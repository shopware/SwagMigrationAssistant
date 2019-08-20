import template from './swag-migration-data-selector.html.twig';
import './swag-migration-data-selector.scss';

const { Component, State } = Shopware;

Component.register('swag-migration-data-selector', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService'
    },

    created() {
        this.createdComponent();
    },

    data() {
        return {
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess'),
            /** @type MigrationUIStore */
            migrationUIStore: State.getStore('migrationUI')
        };
    },

    computed: {
        displayWarnings() {
            return this.migrationProcessStore.state.environmentInformation.displayWarnings;
        },

        uiDataSelectionTableData() {
            return this.migrationUIStore.state.dataSelectionTableData.filter(
                selection => selection.requiredSelection === false
            );
        },

        uiDataSelectionTableDataIdLookup() {
            const lookUp = {};
            this.migrationUIStore.state.dataSelectionTableData.forEach((data) => {
                lookUp[data.id] = data;
            });

            return lookUp;
        }
    },

    methods: {
        async createdComponent() {
            this.fetchTableData();
        },

        fetchTableData() {
            if (this.migrationUIStore.state.dataSelectionTableData.length > 0) {
                this.$nextTick(() => {
                    this.migrationUIStore.state.dataSelectionIds.forEach((id) => {
                        if (this.uiDataSelectionTableDataIdLookup[id].requiredSelection === false) {
                            this.$refs.tableDataGrid.selectItem(true, { id });
                        }
                    });
                });
            }
        },

        onGridSelectItem(selection) {
            const selectionIds = Object.keys(selection);

            if (selectionIds.length > 0) {
                this.migrationUIStore.state.dataSelectionTableData.forEach((data) => {
                    if (data.requiredSelection !== true) {
                        return;
                    }

                    selectionIds.push(data.id);
                });
            }

            this.migrationUIStore.setDataSelectionIds(selectionIds);
        },

        showHelptext(entityTotals) {
            return entityTotals !== undefined && Object.keys(entityTotals).length > 1;
        },

        getHelptext(entityTotals) {
            if (entityTotals === undefined || Object.keys(entityTotals).length === 0) {
                return '';
            }

            let string = '';
            Object.keys(entityTotals).forEach((key) => {
                string += `${this.$tc(`swag-migration.index.selectDataCard.entities.${key}`)
                } ${
                    entityTotals[key]
                }</br>`;
            });

            return string;
        }
    }
});
