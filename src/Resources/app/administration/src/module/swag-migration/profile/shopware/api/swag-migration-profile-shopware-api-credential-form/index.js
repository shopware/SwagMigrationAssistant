import template from './swag-migration-profile-shopware-api-credential-form.html.twig';

const { Component } = Shopware;
const ShopwareError = Shopware.Classes.ShopwareError;
const API_KEY_INVALID_ERROR_CODE = 'SWAG_MIGRATION_INVALID_API_KEY';

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-profile-shopware-api-credential-form', {
    template,

    props: {
        credentials: {
            type: Object,
            default() {
                return {};
            },
        },
    },

    data() {
        return {
            inputCredentials: {
                endpoint: '',
                apiUser: '',
                apiKey: '',
            },
            apiKeyErrorCode: '',
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
            if (this.apiKeyErrorCode === '') {
                return null;
            }

            return new ShopwareError({
                code: this.apiKeyErrorCode,
                meta: {
                    parameters: {
                        length: this.apiKeyLength,
                    },
                },
            });
        },
    },

    watch: {
        credentials: {
            immediate: true,
            handler(newCredentials) {
                if (newCredentials === null || Object.keys(newCredentials).length < 1) {
                    this.emitCredentials(this.inputCredentials);
                    return;
                }

                this.inputCredentials = newCredentials;
                this.emitOnChildRouteReadyChanged(
                    this.areCredentialsValid(this.inputCredentials),
                );
            },
        },

        inputCredentials: {
            deep: true,
            handler(newInputCredentials) {
                this.emitCredentials(newInputCredentials);
            },
        },
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
                this.apiKeyErrorCode = API_KEY_INVALID_ERROR_CODE;
                return false;
            }

            this.apiKeyErrorCode = '';
            return true;
        },

        emitOnChildRouteReadyChanged(isReady) {
            this.$emit('onChildRouteReadyChanged', isReady);
        },

        emitCredentials(newInputCredentials) {
            this.$emit('onCredentialsChanged', newInputCredentials);
            this.emitOnChildRouteReadyChanged(
                this.areCredentialsValid(newInputCredentials),
            );
        },
    },
});
