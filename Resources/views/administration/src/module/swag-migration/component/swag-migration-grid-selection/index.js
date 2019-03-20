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
            const end = Math.min(start + this.limit, this.mapping.length);
            this.items = [];

            // Copy the object references into the display items array (for pagination). Note: Array.slice dont work
            for (let i = start; i < end; i += 1) {
                this.items.push(this.mapping[i]);
            }

            return this.items;
        },

        onInput(event) {
            this.$emit('input', event);
        }
    }
});
