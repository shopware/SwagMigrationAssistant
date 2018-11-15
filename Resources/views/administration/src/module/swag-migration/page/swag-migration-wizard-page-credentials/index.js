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
        endpointChanged(inputValue) {
            if (inputValue === null) {
                this.setEndpointInputValue('');
                return;
            }

            this.checkInput(inputValue);
        },

        checkInput(inputValue) {
            let newValue = inputValue;

            if (newValue.match(/^\s*https?:\/\//) !== null) {
                const sslFound = newValue.match(/^\s*https:\/\//);
                this.sslActive = (sslFound !== null);
                newValue = newValue.replace(/^\s*https?:\/\//, '');
            }

            this.setEndpointInputValue(newValue);
        },

        /**
         * Set the endpointInput variable and also the current value inside the html input.
         * The sw-field does not update the html if there is no change in the binding variable (endpointInput /
         * because it gets watched), so it must be done manually (to replace / remove unwanted user input).
         *
         * @param newValue
         */
        setEndpointInputValue(newValue) {
            this.endpointInput = newValue;
            this.emitEndpoint();

            if (this.$refs.endpointField !== undefined) {
                this.$refs.endpointField.currentValue = this.endpointInput;
            } else {
                this.$nextTick(() => {
                    this.$refs.endpointField.currentValue = this.endpointInput;
                });
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
