import template from './swag-migration-wizard-page-introduction.html.twig';

const { Component } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-wizard-page-introduction', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },
});
