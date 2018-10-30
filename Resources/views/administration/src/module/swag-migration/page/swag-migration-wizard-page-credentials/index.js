import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-credentials.html.twig';
import './swag-migration-wizard-page-credentials.less';

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
            breadcrumbDescription: this.$tc('swag-migration.wizard.pathTarget'),
            sslActive: true,
            endpointInput: ''
        };
    },

    watch: {
        endpoint: {
            immediate: true,
            handler(newEndpoint) {
                this.checkInput(newEndpoint);
            }
        }
    },

    computed: {
        prefixClass() {
            if (this.sslActive) {
                return 'is--ssl';
            }

            return '';
        },

        endpointPrefix() {
            if (this.sslActive) {
                return 'https://';
            }

            return 'http://';
        }
    },

    methods: {
        endpointChanged(input) {
            if (input === null) {
                this.endpointInput = '';
                this.emitEndpoint();
                return;
            }

            this.checkInput(input);
            this.emitEndpoint();
        },

        checkInput(input) {
            if (input.match(/^https?:\/\//) !== null) {
                const sslFound = input.match(/^https:\/\//);
                this.sslActive = (sslFound !== null);
                this.endpointInput = input.replace(/^https?:\/\//, '');
            } else {
                this.endpointInput = input;
            }
        },

        sslChanged(newValue) {
            this.sslActive = newValue;
            this.emitEndpoint();
        },

        emitEndpoint() {
            this.$emit('endpointChanged', this.endpointPrefix + this.endpointInput);
        }
    }
});
