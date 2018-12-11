import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-credentials.html.twig';

Component.register('swag-migration-profile-shopware55-api-credential-form', {
    template,

    props: {
        credentials: {
            type: Object,
            default() {
                return {};
            }
        }
    },

    data() {
        return {
            inputCredentials: {},
            breadcrumbItems: [
                this.$tc('swag-migration.wizard.pathSettings'),
                this.$tc('swag-migration.wizard.pathUserManagement'),
                this.$tc('swag-migration.wizard.pathEditUser')
            ],
            breadcrumbDescription: this.$tc('swag-migration.wizard.pathTarget')
        };
    },

    watch: {
        credentials: {
            immediate: true,
            handler(newCredentials) {
                this.inputCredentials = newCredentials;
                this.$emit('onCredentialsValidationChanged', this.areCredentialsValid(this.inputCredentials));
            }
        },

        inputCredentials: {
            deep: true,
            handler(newInputCredentials) {
                this.emitCredentials(newInputCredentials);
            }
        }
    },

    methods: {
        areCredentialsValid(newInputCredentials) {
            return (newInputCredentials.endpoint &&
                newInputCredentials.apiUser &&
                newInputCredentials.apiKey &&
                newInputCredentials.endpoint !== 'http://' &&
                newInputCredentials.endpoint !== 'https://'
            );
        },

        emitCredentials(newInputCredentials) {
            this.$emit('onCredentialsChanged', newInputCredentials);
            this.$emit('onCredentialsValidationChanged', this.areCredentialsValid(newInputCredentials));
        }
    }
});
