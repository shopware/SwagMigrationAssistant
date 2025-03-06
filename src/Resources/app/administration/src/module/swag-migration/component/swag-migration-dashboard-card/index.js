import template from './swag-migration-dashboard-card.html.twig';
import './swag-migration-dashboard-card.scss';

const { Component } = Shopware;

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Component.register('swag-migration-dashboard-card', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    methods: {
        goToMigrationIndexPage() {
            this.$router.push({ name: 'swag.migration.index' });
        },
    },
});
