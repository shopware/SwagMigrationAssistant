import { Component } from 'src/core/shopware';
import template from './swag-migration-data-selector.html.twig';
import './swag-migration-data-selector.less';


Component.register('swag-migration-data-selector', {
    template,

    props: {
        catalogues: {
            type: Array
        },
        tableData: {
            type: Array
        }
    },

    watch: {
        catalogues() {
            if (this.catalogues.length < 1)
                return;

            this.tableData.forEach((row) => {
                row.catalogueId = this.catalogues[0].id;
            });
        }
    },

    mounted() {
        this.$refs.tableDataGrid.selectAll(true);
    },

    methods: {
        getSelectedData() {
            return this.$refs.tableDataGrid.getSelection();
        },

        getTableData() {
            return this.$refs.tableDataGrid.getSelection();
        }
    }
});