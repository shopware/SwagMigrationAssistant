import { Component } from 'src/core/shopware';
import ShopwareError from 'src/core/data/ShopwareError';
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
            },
            apiKeyErrorSnippet: ''
        };
    },

    computed: {
        apiKeyLength() {
            if (this.inputCredentials.apiKey === null) {
                return 0;
            }

            return this.inputCredentials.apiKey.length;
        },

        apiKeyError() {
            const code = this.apiKeyErrorSnippet !== '' ? 1 : 0;
            const detail = this.apiKeyErrorSnippet !== '' ?
                this.$t(this.apiKeyErrorSnippet, { length: this.apiKeyLength }) :
                '';

            return new ShopwareError({
                code,
                detail
            });
        }
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
            return (
                this.apiKeyValid(newInputCredentials.apiKey) &&
                this.validateInput(newInputCredentials.endpoint) &&
                this.validateInput(newInputCredentials.apiUser) &&
                newInputCredentials.endpoint !== 'http://' &&
                newInputCredentials.endpoint !== 'https://'
            );
        },

        validateInput(input) {
            return input !== null && input !== '';
        },

        apiKeyValid(apiKey) {
            if (apiKey === null || apiKey.length < 40 || apiKey.length > 40) {
                this.apiKeyErrorSnippet = 'swag-migration.wizard.pages.credentials.shopware55.api.apiKeyInvalid';
                return false;
            }

            this.apiKeyErrorSnippet = '';
            return true;
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
