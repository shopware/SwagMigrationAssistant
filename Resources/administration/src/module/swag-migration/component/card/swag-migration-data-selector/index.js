import { Component, State } from 'src/core/shopware';
import template from './swag-migration-data-selector.html.twig';
import './swag-migration-data-selector.scss';

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

    methods: {
        async createdComponent() {
            this.fetchTableData();
        },

        fetchTableData() {
            if (this.migrationUIStore.state.dataSelectionTableData.length > 0) {
                this.$nextTick(() => {
                    this.migrationUIStore.state.dataSelectionIds.forEach((id) => {
                        this.$refs.tableDataGrid.selectItem(true, { id });
                    });
                });
            }
        },

        onGridSelectItem(selection) {
            this.migrationUIStore.setDataSelectionIds(Object.keys(selection));
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
