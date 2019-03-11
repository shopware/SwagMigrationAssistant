import { Component, State } from 'src/core/shopware';
import template from './swag-migration-data-selector.html.twig';
import './swag-migration-data-selector.scss';

Component.register('swag-migration-data-selector', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService'
    },

    data() {
        return {
            tableData: [],
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess'),
            /** @type MigrationUIStore */
            migrationUIStore: State.getStore('migrationUI')
        };
    },

    computed: {
        /**
         * Returns the table data without datasets that don't have any entities.
         *
         * @returns {Array}
         */
        tableDataFiltered() {
            if (this.migrationProcessStore.state.environmentInformation === null) {
                return [];
            }

            const filtered = [];
            this.tableData.forEach((group) => {
                let containtsData = false;
                group.entityNames.forEach((name) => {
                    if (this.migrationProcessStore.state.environmentInformation.totals[name] > 0) {
                        containtsData = true;
                    }
                });

                if (containtsData) {
                    filtered.push(group);
                }
            });

            return filtered;
        }
    },

    watch: {
        'migrationProcessStore.state.connectionId': {
            immediate: true,
            handler(newConnectionId) {
                this.fetchTableData(newConnectionId);
            }
        },

        tableDataFiltered() {
            this.$nextTick(() => {
                this.selectDefault();
            });
        }
    },

    methods: {
        fetchTableData(connectionId) {
            this.migrationService.getDataSelection(connectionId).then((dataSelection) => {
                this.tableData = dataSelection;
            });
        },

        selectDefault() {
            if (this.tableDataFiltered.length > 0) {
                this.$refs.tableDataGrid.selectAll(true);
                this.onGridSelectItem(this.$refs.tableDataGrid.getSelection());
            }
        },

        onGridSelectItem(selection) {
            this.migrationUIStore.setDataSelectionIds(Object.keys(selection));
            this.checkIfMigrationIsAllowed();
        },

        checkIfMigrationIsAllowed() {
            const isMigrationAllowed = (
                this.tableData.length > 0 &&
                this.migrationUIStore.state.dataSelectionIds.length > 0
            );
            this.migrationUIStore.setIsMigrationAllowed(isMigrationAllowed);
        }
    }
});
