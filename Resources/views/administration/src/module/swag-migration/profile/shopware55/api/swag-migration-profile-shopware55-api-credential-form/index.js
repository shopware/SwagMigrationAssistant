import { Component } from 'src/core/shopware';
import template from './swag-migration-profile-shopware55-api-credential-form.html.twig';

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
            inputCredentials: {
                endpoint: '',
                apiUser: '',
                apiKey: ''
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
            return (newInputCredentials.endpoint !== '' &&
                newInputCredentials.apiUser !== '' &&
                newInputCredentials.apiKey !== '' &&
                newInputCredentials.endpoint !== 'http://' &&
                newInputCredentials.endpoint !== 'https://'
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
        }
    }
});
