import {Component} from 'src/core/shopware';
import template from './swag-migration-wizard-page-3.html.twig';

Component.register('swag-migration-wizard-page-3', {
    template,
    data() {
        return {
            breadcrumbItems: [
                this.$tc('sw-migration.wizard.pathSettings'),
                this.$tc('sw-migration.wizard.pathExtensions'),
                this.$tc('sw-migration.wizard.pathMigrationtools')
            ],
            breadcrumbDescription: this.$tc('sw-migration.wizard.pathTarget')
        };
    },
});