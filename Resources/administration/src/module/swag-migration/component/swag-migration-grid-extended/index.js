import template from './swag-migration-grid-extended.html.twig';

const { Component } = Shopware;

Component.extend('swag-migration-grid-extended', 'sw-grid', {
    template,

    props: {
        disabledAttribute: {
            type: String,
            default: 'disabled'
        }
    },

    methods: {
        isDisabled(item) {
            return item[this.disabledAttribute];
        }
    }
});
