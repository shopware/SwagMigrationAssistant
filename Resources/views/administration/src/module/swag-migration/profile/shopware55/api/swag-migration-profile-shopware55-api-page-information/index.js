import { Component } from 'src/core/shopware';
import template from './swag-migration-profile-shopware55-api-page-information.html.twig';

Component.register('swag-migration-profile-shopware55-api-page-information', {
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
