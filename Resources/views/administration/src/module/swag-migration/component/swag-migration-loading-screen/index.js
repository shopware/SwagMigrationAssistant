import { Component } from 'src/core/shopware';
import template from './swag-migration-loading-screen.html.twig';


Component.register('swag-migration-loading-screen', {
    template,

    data() {
        return {
            progress: 20,
        }
    },
});