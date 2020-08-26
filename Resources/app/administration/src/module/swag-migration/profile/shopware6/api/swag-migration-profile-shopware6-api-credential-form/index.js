import template from './swag-migration-profile-shopware6-api-credential-form.html.twig';

const { Component } = Shopware;
const ShopwareError = Shopware.Classes.ShopwareError;
const API_KEY_INVALID_ERROR_CODE = 'SWAG_MIGRATION_INVALID_API_KEY';

Component.register('swag-migration-profile-shopware6-api-credential-form', {
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
                apiPassword: ''
            },
            apiPasswordErrorCode: ''
        };
    },

    computed: {
        apiPasswordLength() {
            if (this.inputCredentials.apiPassword === null) {
                return 0;
            }

            return this.inputCredentials.apiPassword.length;
        },

        apiPasswordError() {
            if (this.apiPasswordErrorCode === '') {
                return null;
            }

            return new ShopwareError({
                code: this.apiPasswordErrorCode,
                meta: {
                    parameters: {
                        length: this.apiPasswordLength
                    }
                }
            });
        }
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
                    this.areCredentialsValid(this.inputCredentials)
                );
            }
        },

        inputCredentials: {
            deep: true,
            handler(newInputCredentials) {
                delete newInputCredentials.bearer_token;
                this.emitCredentials(newInputCredentials);
            }
        }
    },

    methods: {
        areCredentialsValid(newInputCredentials) {
            return (
                this.apiPasswordValid(newInputCredentials.apiPassword) &&
                this.validateInput(newInputCredentials.endpoint) &&
                this.validateInput(newInputCredentials.apiUser) &&
                newInputCredentials.endpoint !== 'http://' &&
                newInputCredentials.endpoint !== 'https://'
            );
        },

        validateInput(input) {
            return input !== null && input !== '';
        },

        apiPasswordValid(apiPassword) {
            if (apiPassword === null || apiPassword.length < 1) {
                this.apiPasswordErrorCode = API_KEY_INVALID_ERROR_CODE;
                return false;
            }

            this.apiPasswordErrorCode = '';
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
