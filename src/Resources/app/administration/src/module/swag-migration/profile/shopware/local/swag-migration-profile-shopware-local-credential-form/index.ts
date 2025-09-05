import template from './swag-migration-profile-shopware-local-credential-form.html.twig';

type Credentials = {
    dbHost: string;
    dbPort: string;
    dbUser: string;
    dbPassword: string;
    dbName: string;
    installationRoot: string;
};

/**
 * @private
 */
export interface SwagMigrationProfileShopwareLocalCredentialFormData {
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

    data(): SwagMigrationProfileShopwareLocalCredentialFormData {
        return {
            inputCredentials: {
                dbHost: '',
                dbPort: '3306',
                dbUser: '',
                dbPassword: '',
                dbName: '',
                installationRoot: '',
            },
        };
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
        areCredentialsValid(newInputCredentials: Credentials) {
            return (
                newInputCredentials.dbHost !== '' &&
                newInputCredentials.dbPort !== '' &&
                newInputCredentials.dbName !== '' &&
                newInputCredentials.dbUser !== '' &&
                newInputCredentials.dbPassword !== '' &&
                newInputCredentials.installationRoot !== ''
            );
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
