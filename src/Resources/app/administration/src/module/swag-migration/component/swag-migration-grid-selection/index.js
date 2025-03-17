import template from './swag-migration-grid-selection.html.twig';
import './swag-migration-grid-selection.scss';

const { Component, Mixin } = Shopware;

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Component.register('swag-migration-grid-selection', {
    template,

    emits: ['update:value'],

    mixins: [
        Mixin.getByName('listing'),
    ],

    props: {
        choices: {
            type: Array,
            required: true,
        },
        mapping: {
            type: Array,
            required: true,
        },
    },

    data() {
        return {
            items: [],
            disableRouteParams: true,
            limit: 10,
            paginationSteps: [10, 20, 30, 50],
            selectOptions: [],
        };
    },

    watch: {
        choices: {
            handler(newChoices) {
                if (!newChoices) return;

                this.selectOptions = newChoices.map((choice) => ({
                    label: choice.description,
                    value: choice.uuid,
                }));
            },
            deep: true,
            immediate: true,
        },
        mapping() {
            this.getList();
        },
    },

    methods: {
        getList() {
            this.total = this.mapping.length;
            const start = (this.page - 1) * this.limit;
            const end = Math.min(start + this.limit, this.total);
            this.items.length = 0; // clear the items array without creating a new reference

            // Copy the object references into the display items array (for pagination).
            // Note: Array.slice does not work (as it copies)
            for (let i = start; i < end; i += 1) {
                this.items.push(this.mapping[i]);
            }

            return this.items;
        },

        onInput() {
            this.$emit('update:value');
        },

        getClassesAfterValidation(item) {
            const hasError = item.destinationUuid === null || item.destinationUuid.length === 0;
            return { 'has--error': hasError };
        },
    },
});
