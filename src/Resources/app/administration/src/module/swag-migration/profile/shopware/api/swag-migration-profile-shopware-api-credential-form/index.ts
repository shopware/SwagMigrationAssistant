import template from './swag-migration-profile-shopware-api-credential-form.html.twig';

const ShopwareError = Shopware.Classes.ShopwareError;
const API_KEY_INVALID_ERROR_CODE = 'SWAG_MIGRATION_INVALID_API_KEY';

type Credentials = {
    endpoint?: string;
    apiUser?: string;
    apiKey?: string;
};

/**
 * @private
 */
export interface SwagMigrationProfileShopwareApiCredentialFormData {
    inputCredentials: Credentials;
    apiKeyErrorCode: string;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    emits: [
        'onChildRouteReadyChanged',
        'onCredentialsChanged',
    ],

    props: {
        credentials: {
            type: Object as PropType<Credentials>,
            default() {
                return {};
            },
        },
    },

    data(): SwagMigrationProfileShopwareApiCredentialFormData {
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

        apiKeyError(): InstanceType<typeof ShopwareError> | null {
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

        storeLink() {
            return `https://store.shopware.com/${this.storeLinkISO}/swag226607479310f/migration-connector.html`;
        },

        storeLinkISO() {
            const iso = this.locale.split('-')[0];

            if (
                [
                    'en',
                    'de',
                ].includes(iso)
            ) {
                return iso;
            }

            return 'en';
        },

        locale() {
            return Shopware.Store.get('session').currentLocale ?? '';
        },
    },

    watch: {
        credentials: {
            immediate: true,
            handler(newCredentials: Credentials | null) {
                if (newCredentials === null || Object.keys(newCredentials).length < 1) {
                    this.emitCredentials(this.inputCredentials);
                    return;
                }

                this.inputCredentials = newCredentials;
                this.emitOnChildRouteReadyChanged(this.areCredentialsValid(this.inputCredentials));
            },
        },

        inputCredentials: {
            deep: true,
            handler(newInputCredentials: Credentials) {
                this.emitCredentials(newInputCredentials);
            },
        },
    },

    methods: {
        areCredentialsValid(newInputCredentials: Credentials): boolean {
            return (
                this.apiKeyValid(newInputCredentials.apiKey) &&
                this.validateInput(newInputCredentials.endpoint) &&
                this.validateInput(newInputCredentials.apiUser) &&
                newInputCredentials.endpoint !== 'http://' &&
                newInputCredentials.endpoint !== 'https://'
            );
        },

        validateInput(input: string | null) {
            return input !== null && input !== '';
        },

        apiKeyValid(apiKey: string | null) {
            if (apiKey === null || apiKey.length < 40 || apiKey.length > 40) {
                this.apiKeyErrorCode = API_KEY_INVALID_ERROR_CODE;
                return false;
            }

            this.apiKeyErrorCode = '';
            return true;
        },

        emitOnChildRouteReadyChanged(isReady: boolean) {
            this.$emit('onChildRouteReadyChanged', isReady);
        },

        emitCredentials(newInputCredentials: Credentials) {
            this.$emit('onCredentialsChanged', newInputCredentials);
            this.emitOnChildRouteReadyChanged(this.areCredentialsValid(newInputCredentials));
        },
    },
});
