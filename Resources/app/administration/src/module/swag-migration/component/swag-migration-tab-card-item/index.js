import template from './swag-migration-tab-card-item.html.twig';

const { Component } = Shopware;
const utils = Shopware.Utils;

Component.register('swag-migration-tab-card-item', {
    template,

    props: {
        title: {
            type: String,
            required: true
        },

        isGrid: {
            type: Boolean,
            default: false,
            required: false
        },

        errorBadgeNumber: {
            type: Number,
            default: 0,
            required: false
        }
    },

    data() {
        return {
            id: utils.createId(),
            active: false
        };
    },

    methods: {
        setActive(active) {
            this.active = active;
        }
    }
});
