import { Component } from 'src/core/shopware';
import template from './swag-migration-empty-screen.html.twig';
import './swag-migration-empty-screen.scss';

Component.register('swag-migration-empty-screen', {
    template,

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    methods: {
        onConnectClick() {
            this.$router.push({ name: 'swag.migration.wizard.introduction' });
        }
    }
});
