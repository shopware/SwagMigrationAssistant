import { Component, State } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import ShopwareError from 'src/core/data/ShopwareError';
import template from './swag-migration-wizard-page-connection-create.html.twig';

Component.register('swag-migration-wizard-page-connection-create', {
    template,

    props: {
        connectionNameErrorSnippet: {
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

        profileStore() {
            return State.getStore('swag_migration_profile');
        },

        generalSettingStore() {
            return State.getStore('swag_migration_general_setting');
        },

        connectionNameError() {
            const code = this.connectionNameErrorSnippet !== '' ? 1 : 0;
            const detail = this.connectionNameErrorSnippet !== '' ?
                this.$tc(this.connectionNameErrorSnippet) :
                '';

            return new ShopwareError({
                code,
                detail
            });
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.setIsLoading(true);
            this.emitOnChildRouteReadyChanged(false);

            this.profileStore.getList({
                limit: 100,
                aggregations: [
                    {
                        name: 'profileAgg',
                        field: 'name',
                        type: 'value'
                    }
                ]
            }).then((profiles) => {
                this.profiles = profiles.aggregations.profileAgg[0].values;

                this.generalSettingStore.getList({ limit: 1 }).then((response) => {
                    if (!response || response.items[0].selectedProfileId === null) {
                        this.selectDefaultProfile();
                        this.setIsLoading(false);
                        return;
                    }

                    this.profileStore.getByIdAsync(response.items[0].selectedProfileId).then((profileResponse) => {
                        if (profileResponse.id === null) {
                            this.selectDefaultProfile();
                            this.setIsLoading(false);
                            return;
                        }

                        this.selection.profile = profileResponse.name;
                        this.onSelectProfile().then(() => {
                            this.selection.gateway = profileResponse.gatewayName;
                            this.onSelectGateway().then(() => {
                                this.emitOnChildRouteReadyChanged(true);
                                this.setIsLoading(false);
                            });
                        });
                    });
                });
            });
        },

        selectDefaultProfile() {
            this.selection.profile = 'shopware55';
            this.onSelectProfile();
        },

        setIsLoading(value) {
            this.isLoading = value;
            this.$emit('onIsLoadingChanged', this.isLoading);
        },

        onSelectProfile() {
            return new Promise((resolve) => {
                this.emitOnChildRouteReadyChanged(false);
                this.gateways = null;

                if (this.selection.profile !== null) {
                    const criteria = CriteriaFactory.equals('name', this.selection.profile);

                    this.profileStore.getList({
                        criteria: criteria,
                        limit: 100,
                        aggregations: [
                            {
                                name: 'gatewayAgg',
                                type: 'value',
                                field: 'gatewayName'
                            }
                        ]
                    }).then((profiles) => {
                        this.gateways = profiles.aggregations.gatewayAgg[0].values;
                        this.selection.gateway = null;
                        this.emitOnChildRouteReadyChanged(this.isReady);
                        resolve();
                    });
                }
            });
        },

        onSelectGateway() {
            return new Promise((resolve) => {
                this.emitOnChildRouteReadyChanged(false);

                const criteria = CriteriaFactory.multi(
                    'AND',
                    CriteriaFactory.equals('name', this.selection.profile),
                    CriteriaFactory.equals('gatewayName', this.selection.gateway)
                );

                this.profileStore.getList({
                    criteria: criteria,
                    limit: 100
                }).then((profile) => {
                    if (profile.total !== 0) {
                        this.$emit('onProfileSelected', profile.items[0]);
                        this.emitOnChildRouteReadyChanged(this.isReady);
                    }
                    resolve();
                });
            });
        },

        onChangeConnectionName(value) {
            this.$emit('onChangeConnectionName', value);
            this.emitOnChildRouteReadyChanged(this.isReady);
        },

        emitOnChildRouteReadyChanged(isReady) {
            this.$emit('onChildRouteReadyChanged', isReady);
        }
    }
});
