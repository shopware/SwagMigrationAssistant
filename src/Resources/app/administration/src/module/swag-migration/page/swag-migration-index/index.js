import template from './swag-migration-index.html.twig';

const { Component } = Shopware;

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Component.extend('swag-migration-index', 'swag-migration-base', {
    template,

    computed: {
        tabItems() {
            return [
                {
                    name: 'swag.migration.index.main',
                    label: this.$tc('swag-migration.general.tabMain'),
                },
                {
                    name: 'swag.migration.index.dataSelector',
                    label: this.$tc('swag-migration.general.tabDataSelector'),
                },
                {
                    name: 'swag.migration.index.history',
                    label: this.$tc('swag-migration.general.tabHistory'),
                },
            ];
        },
    },

    methods: {
        setActiveTab(tabItem) {
            this.$router.push({ name: tabItem.name });
        },
    },
});
