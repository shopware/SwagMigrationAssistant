import template from './swag-migration-wizard-page-connection-create.html.twig';
import './swag-migration-wizard-page-connection-create.scss';

const { Component } = Shopware;
const ShopwareError = Shopware.Classes.ShopwareError;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-wizard-page-connection-create', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationApiService */
        migrationApiService: 'migrationApiService',
    },

    props: {
        connectionNameErrorCode: {
            type: String,
            default: '',
            required: false,
        },
    },

    data() {
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

        connectionNameError() {
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

        profileSearch(searchParams) {
            const searchTerm = searchParams.searchTerm;
            return searchParams.options.filter(option => {
                const label = `${option.sourceSystemName} ${option.version} - ${option.author}`;
                return label.toLowerCase().includes(searchTerm.toLowerCase());
            });
        },

        gatewaySearch(searchParams) {
            const searchTerm = searchParams.searchTerm;
            return searchParams.options.filter(option => {
                const label = this.$tc(option.snippet);
                return label.toLowerCase().includes(searchTerm.toLowerCase());
            });
        },

        getText(item) {
            return `${item.sourceSystemName} ${item.version} - <i>${item.author}</i>`;
        },

        async selectDefaultProfile() {
            await this.onSelectProfile('shopware55');
            this.onSelectGateway('api');
        },

        setIsLoading(value) {
            this.isLoading = value;
            this.$emit('onIsLoadingChanged', this.isLoading);
        },

        onSelectProfile(value) {
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
                        resolve();
                    });
                }
            });
        },

        onSelectGateway(value) {
            if (value !== null && value !== undefined) {
                this.selection.gateway = value;
            }

            this.emitOnChildRouteReadyChanged(false);
            this.$emit('onProfileSelected', this.selection);
            this.emitOnChildRouteReadyChanged(this.isReady);
        },

        onChangeConnectionName(value) {
            this.$emit('onChangeConnectionName', value);
            this.emitOnChildRouteReadyChanged(this.isReady);
        },

        emitOnChildRouteReadyChanged(isReady) {
            this.$emit('onChildRouteReadyChanged', isReady);
        },
    },
});
