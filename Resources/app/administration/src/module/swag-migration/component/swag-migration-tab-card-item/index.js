import template from './swag-migration-tab-card-item.html.twig';

const { Component } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-tab-card-item', {
    template,

    data() {
        return {
            active: false,
        };
    },

    methods: {
        setActive(active) {
            this.active = active;
        },
    },
});
