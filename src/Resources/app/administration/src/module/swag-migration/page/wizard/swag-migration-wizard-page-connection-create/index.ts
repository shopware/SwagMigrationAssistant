import template from './swag-migration-wizard-page-connection-create.html.twig';
import './swag-migration-wizard-page-connection-create.scss';
import type { MigrationGateway, MigrationProfile } from '../../../../../type/types';
import { MIGRATION_API_SERVICE } from '../../../../../core/service/api/swag-migration.api.service';

const ShopwareError = Shopware.Classes.ShopwareError;

type SearchParams = {
    searchTerm: string;
    options: MigrationProfile[] | MigrationGateway[];
};

/**
 * @private
 */
export interface SwagMigrationWizardPageConnectionCreateData {
    isLoading: boolean;
    selection: {
        profile: string | null;
        gateway: string | null;
        connectionName: string | null;
    };
    profiles: MigrationProfile[];
    gateways: MigrationGateway[];
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
    ],

    emits: [
        'onIsLoadingChanged',
        'onProfileSelected',
        'onChangeConnectionName',
        'onChildRouteReadyChanged',
    ],

    props: {
        connectionNameErrorCode: {
            type: String,
            default: '',
            required: false,
        },
    },

    data(): SwagMigrationWizardPageConnectionCreateData {
        return {
            isLoading: true,
            selection: {
                profile: null,
                gateway: null,
                connectionName: null,
            },
            profiles: [],
            gateways: [],
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        isReady() {
            return (
                this.selection.profile !== null &&
                this.selection.gateway !== null &&
                this.selection.connectionName !== null &&
                this.selection.connectionName.length > 0
            );
        },

        connectionNameError(): ShopwareError | null {
            if (this.connectionNameErrorCode === '') {
                return null;
            }

            return new ShopwareError({
                code: this.connectionNameErrorCode,
            });
        },

        profileHint() {
            if (!this.selection.gateway) {
                return '';
            }

            const snippet = `swag-migration.wizard.pages.connectionCreate.hint.${this.selection.gateway}`;

            if (this.$tc(snippet) !== `swag-migration.wizard.pages.connectionCreate.hint.${this.selection.gateway}`) {
                return this.$tc(snippet);
            }

            return '';
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.setIsLoading(true);
            this.emitOnChildRouteReadyChanged(false);

            this.profiles = await this.migrationApiService.getProfiles();
            this.pushLinkToProfiles();

            await this.selectDefaultProfile();
            this.setIsLoading(false);
        },

        pushLinkToProfiles() {
            this.profiles.push({
                name: 'profileLink',
            });
        },

        profileSearch(searchParams: SearchParams) {
            const searchTerm = searchParams.searchTerm;

            return searchParams.options.filter((option: MigrationProfile) => {
                const label = `${option.sourceSystemName} ${option.version} - ${option.author}`;
                return label.toLowerCase().includes(searchTerm.toLowerCase());
            });
        },

        gatewaySearch(searchParams: SearchParams) {
            const searchTerm = searchParams.searchTerm;

            return searchParams.options.filter((option: MigrationGateway) => {
                const label = this.$tc(option.snippet);
                return label.toLowerCase().includes(searchTerm.toLowerCase());
            });
        },

        getText(item: MigrationProfile): string {
            return `${item.sourceSystemName} ${item.version} - <i>${item.author}</i>`;
        },

        async selectDefaultProfile() {
            await this.onSelectProfile('shopware55');
            this.onSelectGateway('api');
        },

        setIsLoading(value: boolean) {
            this.isLoading = value;
            this.$emit('onIsLoadingChanged', this.isLoading);
        },

        onSelectProfile(value: string | null) {
            if (value === null || value === undefined) {
                return Promise.resolve();
            }

            if (value === 'profileLink') {
                this.$router.push({ name: 'swag.migration.wizard.profileInstallation' });
                return Promise.resolve();
            }

            this.selection.profile = value;

            return new Promise((resolve) => {
                this.emitOnChildRouteReadyChanged(false);
                this.gateways = [];
                this.selection.gateway = null;

                if (this.selection.profile !== null) {
                    this.migrationApiService.getGateways(this.selection.profile).then((gateways) => {
                        this.gateways = gateways;
                        this.selection.gateway = null;

                        if (this.gateways.length === 1) {
                            this.$nextTick(() => {
                                this.selection.gateway = this.gateways[0].name;
                                this.emitOnChildRouteReadyChanged(this.isReady);
                            });
                        }

                        this.emitOnChildRouteReadyChanged(this.isReady);
                        resolve(null);
                    });
                }
            });
        },

        onSelectGateway(value: string | null) {
            if (value !== null && value !== undefined) {
                this.selection.gateway = value;
            }

            this.emitOnChildRouteReadyChanged(false);
            this.$emit('onProfileSelected', this.selection);
            this.emitOnChildRouteReadyChanged(this.isReady);
        },

        onChangeConnectionName(value: string | null) {
            this.$emit('onChangeConnectionName', value);
            this.emitOnChildRouteReadyChanged(this.isReady);
        },

        emitOnChildRouteReadyChanged(isReady: boolean) {
            this.$emit('onChildRouteReadyChanged', isReady);
        },
    },
});
