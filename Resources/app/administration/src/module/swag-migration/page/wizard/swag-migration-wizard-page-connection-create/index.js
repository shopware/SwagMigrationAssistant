import template from './swag-migration-wizard-page-connection-create.html.twig';
import './swag-migration-wizard-page-connection-create.scss';

const { Component } = Shopware;
const ShopwareError = Shopware.Classes.ShopwareError;

Component.register('swag-migration-wizard-page-connection-create', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService'
    },

    props: {
        connectionNameErrorCode: {
            type: String,
            default: '',
            required: false
        }
    },

    data() {
        return {
            isLoading: true,
            selection: {
                profile: null,
                gateway: null,
                connectionName: null
            },
            profiles: [],
            gateways: []
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        isReady() {
            return (
                this.selection.profile !== null &&
                this.selection.gateway !== null &&
                this.selection.connectionName !== null &&
                this.selection.connectionName.length > 5
            );
        },

        connectionNameError() {
            if (this.connectionNameErrorCode === '') {
                return null;
            }

            return new ShopwareError({
                code: this.connectionNameErrorCode
            });
        },

        profileHint() {
            if (!this.selection.gateway) {
                return '';
            }

            const snippet = `swag-migration.wizard.pages.connectionCreate.hint.${this.selection.gateway}`;
            if (this.$te(snippet)) {
                return this.$t(snippet);
            }

            return '';
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        profileSearch(searchParams) {
            const searchTerm = searchParams.searchTerm;
            return searchParams.options.filter(option => {
                const label = `${option.sourceSystemName} ${option.version} - ${option.author}`;
                return label.toLowerCase().includes(searchTerm.toLowerCase());
            });
        },

        getText(item) {
            return `${item.sourceSystemName} ${item.version} - <i>${item.author}</i>`;
        },

        createdComponent() {
            this.setIsLoading(true);
            this.emitOnChildRouteReadyChanged(false);

            this.migrationService.getProfiles().then((profiles) => {
                this.profiles = profiles;

                this.selectDefaultProfile();
                this.setIsLoading(false);
            });
        },

        selectDefaultProfile() {
            this.selection.profile = 'shopware55';
            this.onSelectProfile().then(() => {
                this.selection.gateway = 'api';
                this.$nextTick(() => {
                    this.onSelectGateway();
                });
            });
        },

        setIsLoading(value) {
            this.isLoading = value;
            this.$emit('onIsLoadingChanged', this.isLoading);
        },

        onSelectProfile(value) {
            if (this.selection.profile === value || value === null) {
                return new Promise();
            }

            if (value !== null && value !== undefined) {
                this.selection.profile = value;
            }

            return new Promise((resolve) => {
                this.emitOnChildRouteReadyChanged(false);
                this.gateways = null;
                this.selection.gateway = null;

                if (this.selection.profile !== null) {
                    this.migrationService.getGateways(this.selection.profile).then((gateways) => {
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

        onSelectGateway() {
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

        onKeyPressEnter() {
            this.$emit('onTriggerPrimaryClick');
        }
    }
});
