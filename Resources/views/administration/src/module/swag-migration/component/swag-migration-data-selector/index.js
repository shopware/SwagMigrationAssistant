import { Component } from 'src/core/shopware';
import template from './swag-migration-data-selector.html.twig';
import './swag-migration-data-selector.less';


Component.register('swag-migration-data-selector', {
    template,

    props: {
        targets: {
            type: Array
        },
        tableData: {
            type: Array
        }
    },

    data() {
        return {
            selection: {}
        };
    },

    watch: {
        targets: {
            handler: function targetHandler() {
                if (this.targets.length < 1) {
                    return;
                }

                this.tableData.forEach((data) => {
                    data.targetId = this.targets[0].id;
                });
            },
            immediate: true
        }
    },

    mounted() {
        this.$refs.tableDataGrid.selectAll(true);
        this.onGridSelectItem(this.$refs.tableDataGrid.getSelection());
    },

    methods: {
        getSelectedData() {
            return this.$refs.tableDataGrid.getSelection();
        },

        getTableData() {
            return this.$refs.tableDataGrid.getSelection();
        },
        onGridSelectItem(selection) {
            this.selection = selection;
            const oldShowGdpr = this.showGdpr;
            this.showGdpr = !!this.selection.customers_orders;
            if (this.showGdpr && oldShowGdpr !== this.showGdpr) {
                this.gdprChecked = false;
            }
            this.checkIfMigrationIsAllowed();
        },
        checkIfMigrationIsAllowed() {
            if (Object.keys(this.selection).length > 0) {
                this.$emit('swag-migration-data-selector-migration-allowed', true);
            } else {
                this.$emit('swag-migration-data-selector-migration-allowed', false);
            }
        }
    }
});
