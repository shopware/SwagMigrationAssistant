import {Component} from 'src/core/shopware';
import template from './swag-migration-wizard-page-3.html.twig';

Component.register('swag-migration-wizard-page-3', {
    template,
    data() {
        return {
            breadcrumbItems: [
                this.$tc('swag-migration.wizard.pathSettings'),
                this.$tc('swag-migration.wizard.pathExtensions'),
                this.$tc('swag-migration.wizard.pathMigrationtools')
            ],
            breadcrumbDescription: this.$tc('swag-migration.wizard.pathTarget')
        };
    },
});