import template from './swag-migration-loading-screen-connection-lost.html.twig';
import './swag-migration-loading-screen-connection-lost.scss';

const { Component } = Shopware;

Component.register('swag-migration-loading-screen-connection-lost', {
    template,

    methods: {
        onNavigateMainClick() {
            window.location.reload(); // trigger full page refresh, because the user can be still offline.
        },
    },
});
