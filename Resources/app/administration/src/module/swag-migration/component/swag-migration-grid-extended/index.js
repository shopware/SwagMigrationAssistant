import template from './swag-migration-grid-extended.html.twig';

const { Component } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.extend('swag-migration-grid-extended', 'sw-grid', {
    template,

    props: {
        disabledAttribute: {
            type: String,
            default: 'disabled',
        },
    },

    methods: {
        isDisabled(item) {
            return item[this.disabledAttribute];
        },

        extendedGridRowClasses(item, index) {
            const classes = {
                'is--selected': this.isSelected(item.id) && !this.isDisabled(item),
                'is--deleted': item.isDeleted,
                'is--new': item.isLocal,
                'is--disabled': this.isDisabled(item),
            };

            classes[`sw-grid__row--${index}`] = true;

            return classes;
        },
    },
});
