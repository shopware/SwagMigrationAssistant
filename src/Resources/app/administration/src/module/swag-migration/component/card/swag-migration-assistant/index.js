import template from './swag-migration-assistant.html.twig';
import './swag-migration-assistant.scss';

const { Component } = Shopware;

/**
 * @package services-settings
 */
Component.register('swag-migration-assistant', {
    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    template,
});
