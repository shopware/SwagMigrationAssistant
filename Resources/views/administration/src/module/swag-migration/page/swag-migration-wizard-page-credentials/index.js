import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-credentials.html.twig';

Component.register('swag-migration-wizard-page-credentials', {
    template,

    props: {
        apiKey: {
            type: String,
            default: ''
        },
        apiUser: {
            type: String,
            default: ''
        },
        endpoint: {
            type: String,
            default: ''
        }
    },

    data() {
        return {
            breadcrumbItems: [
                this.$tc('swag-migration.wizard.pathSettings'),
                this.$tc('swag-migration.wizard.pathUserManagement'),
                this.$tc('swag-migration.wizard.pathEditUser')
            ],
            breadcrumbDescription: this.$tc('swag-migration.wizard.pathTarget')
        };
    }
});
