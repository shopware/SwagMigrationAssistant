import template from './swag-migration-profile-shopware6-api-credential-form.html.twig';

type Credentials = {
    endpoint?: string;
    apiUser?: string;
    apiPassword?: string;
    bearer_token?: string;
};

/**
 * @private
 */
export interface SwagMigrationProfileShopware6ApiCredentialFormData {
    inputCredentials: Credentials;
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

    data(): SwagMigrationProfileShopware6ApiCredentialFormData {
        return {
            inputCredentials: {
                endpoint: '',
                apiUser: '',
                apiPassword: '',
            },
        };
    },

    computed: {
        apiPasswordLength() {
            if (this.inputCredentials.apiPassword === null) {
                return 0;
            }

            return this.inputCredentials.apiPassword.length;
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
                delete newInputCredentials.bearer_token;
                this.emitCredentials(newInputCredentials);
            },
        },
    },

    methods: {
        areCredentialsValid(newInputCredentials: Credentials) {
            return (
                this.apiPasswordValid(newInputCredentials.apiPassword) &&
                this.validateInput(newInputCredentials.endpoint) &&
                this.validateInput(newInputCredentials.apiUser) &&
                newInputCredentials.endpoint !== 'http://' &&
                newInputCredentials.endpoint !== 'https://'
            );
        },

        validateInput(input: string | null) {
            return input !== null && input !== '';
        },

        apiPasswordValid(apiPassword: string | null) {
            return apiPassword !== null && apiPassword.length >= 1;
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
