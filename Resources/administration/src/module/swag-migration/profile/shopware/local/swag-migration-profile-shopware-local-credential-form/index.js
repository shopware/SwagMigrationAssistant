import { Component } from 'src/core/shopware';
import template from '../../../shopware/local/swag-migration-profile-shopware-local-credential-form/swag-migration-profile-shopware-local-credential-form.html.twig';

Component.register('swag-migration-profile-shopware-local-credential-form', {
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
            inputCredentials: {
                dbHost: '',
                dbPort: '3306',
                dbUser: '',
                dbPassword: '',
                dbName: '',
                installationRoot: ''
            }
        };
    },

    watch: {
        credentials: {
            immediate: true,
            handler(newCredentials) {
                if (newCredentials === null) {
                    this.emitCredentials(this.inputCredentials);
                    return;
                }

                this.inputCredentials = newCredentials;
                this.emitOnChildRouteReadyChanged(
                    this.areCredentialsValid(this.inputCredentials)
                );
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
            return (newInputCredentials.dbHost !== '' &&
                newInputCredentials.dbPort !== '' &&
                newInputCredentials.dbName !== '' &&
                newInputCredentials.dbUser !== '' &&
                newInputCredentials.dbPassword !== '' &&
                newInputCredentials.installationRoot !== ''
            );
        },

        emitOnChildRouteReadyChanged(isReady) {
            this.$emit('onChildRouteReadyChanged', isReady);
        },

        emitCredentials(newInputCredentials) {
            this.$emit('onCredentialsChanged', newInputCredentials);
            this.emitOnChildRouteReadyChanged(
                this.areCredentialsValid(newInputCredentials)
            );
        },

        onKeyPressEnter() {
            this.$emit('onTriggerPrimaryClick');
        }
    }
});
