import template from './swag-migration-data-selector.html.twig';
import './swag-migration-data-selector.scss';

const { Component, State } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-migration-data-selector', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService',
    },

    computed: {
        ...mapState('swagMigration/process', [
            'environmentInformation',
        ]),

        ...mapState('swagMigration/ui', [
            'dataSelectionTableData',
            'dataSelectionIds',
        ]),

        displayWarnings() {
            return this.environmentInformation.displayWarnings;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.fetchTableData();
        },

        fetchTableData() {
            if (this.dataSelectionTableData.length > 0) {
                this.$nextTick(() => {
                    this.dataSelectionIds.forEach((id) => {
                        this.$refs.tableDataGrid.selectItem(true, { id });
                    });
                });
            }
        },

        onGridSelectItem(selection) {
            const selectionIds = Object.keys(selection);

            this.dataSelectionTableData.forEach((data) => {
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

            State.commit('swagMigration/ui/setDataSelectionIds', selectionIds);
        },

        showHelptext(entityTotals) {
            return entityTotals !== undefined && Object.keys(entityTotals).length > 1;
        },

        getHelptext(item) {
            if (item.entityTotals === undefined || Object.keys(item.entityTotals).length === 0) {
                return '';
            }

            let string = '';
            Object.keys(item.entityTotals).forEach((key) => {
                string += `${this.$tc(item.entityNames[key])
                } ${
                    item.entityTotals[key]
                }</br>`;
            });

            return string;
        },
    },
});
