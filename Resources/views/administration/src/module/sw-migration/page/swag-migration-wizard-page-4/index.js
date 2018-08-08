import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-4.html.twig';

Component.register('swag-migration-wizard-page-4', {
    template,

    data() {
        return {
            breadcrumbItems: [
                this.$tc('sw-migration.wizard.pathSettings'),
                this.$tc('sw-migration.wizard.pathUserManagement'),
                this.$tc('sw-migration.wizard.pathEditUser')
            ],
            breadcrumbDescription: this.$tc('sw-migration.wizard.pathTarget')
        };
    },
});