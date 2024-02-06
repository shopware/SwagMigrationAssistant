import template from './swag-migration-dashboard-card.html.twig';
import './swag-migration-dashboard-card.scss';

const { Component } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-dashboard-card', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
});
