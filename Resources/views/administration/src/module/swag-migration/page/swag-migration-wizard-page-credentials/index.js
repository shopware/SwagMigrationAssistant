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
            localApiKey: '',
            localApiUser: '',
            localEndpoint: '',
            breadcrumbItems: [
                this.$tc('swag-migration.wizard.pathSettings'),
                this.$tc('swag-migration.wizard.pathUserManagement'),
                this.$tc('swag-migration.wizard.pathEditUser')
            ],
            breadcrumbDescription: this.$tc('swag-migration.wizard.pathTarget')
        };
    },

    created() {
        this.updateApiKey();
        this.updateApiUser();
        this.updateEndpoint();
    },

    watch: {
        apiKey() {
            this.updateApiKey();
        },
        apiUser() {
            this.updateApiUser();
        },
        endpoint() {
            this.updateEndpoint();
        }
    },

    methods: {
        updateApiKey() {
            this.localApiKey = this.apiKey;
        },
        updateApiUser() {
            this.localApiUser = this.apiUser;
        },
        updateEndpoint() {
            this.localEndpoint = this.endpoint;
        }
    }
});
