import { Component, Mixin } from 'src/core/shopware';
import template from './swag-migration-grid-selection.html.twig';
import './swag-migration-grid-selection.scss';

Component.register('swag-migration-grid-selection', {
    template,

    mixins: [
        Mixin.getByName('listing')
    ],

    props: {
        choices: {
            type: Array,
            required: true
        },
        mapping: {
            type: Array,
            required: true
        }
    },

    data() {
        return {
            items: [],
            disableRouteParams: true,
            limit: 10,
            paginationSteps: [5, 10, 15, 20, 25, 30]
        };
    },

    methods: {
        getList() {
            this.total = this.mapping.length;
            const start = (this.page - 1) * this.limit;
            const end = start + this.limit;
            this.items = this.mapping.slice(
                start,
                end
            );

            return this.items;
        }
    }
});
