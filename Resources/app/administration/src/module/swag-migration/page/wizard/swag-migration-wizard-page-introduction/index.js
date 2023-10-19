import template from './swag-migration-wizard-page-introduction.html.twig';

const { Component } = Shopware;

/**
 * @package services-settings
 */
Component.register('swag-migration-wizard-page-introduction', {
    template,

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },
});
