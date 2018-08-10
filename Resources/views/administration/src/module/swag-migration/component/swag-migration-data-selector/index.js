import { Component } from 'src/core/shopware';
import template from './swag-migration-data-selector.html.twig';
import './swag-migration-data-selector.less';


Component.register('swag-migration-data-selector', {
    template,

    props: {
        catalogues: {
            type: Array,
            required: true,
            default: []
        }
    },

    data() {
        return {
            tableData: [
                {
                    id: 0,
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.customersAndOrders'),
                    catalogueId: 1
                },
                {
                    id: 1,
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.categoriesAndProducts'),
                    catalogueId: 1
                },
                {
                    id: 2,
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.media'),
                    catalogueId: 1
                }
            ],
        };
    },

    mounted() {
        this.$refs.tableDataGrid.selectAll(true);
    }
});