import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-plugin-information.html.twig';

Component.register('swag-migration-wizard-page-plugin-information', {
    template,
    data() {
        return {
            breadcrumbItems: [
                this.$tc('swag-migration.wizard.pathSettings'),
                this.$tc('swag-migration.wizard.pathPluginManagement')
            ],
            breadcrumbDescription: this.$tc('swag-migration.wizard.pathTarget')
        };
    }
});
