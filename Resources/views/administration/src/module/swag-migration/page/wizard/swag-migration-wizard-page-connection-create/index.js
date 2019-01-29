import { Component, State } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import template from './swag-migration-wizard-page-connection-create.html.twig';

Component.register('swag-migration-wizard-page-connection-create', {
    template,

    created() {
        this.createdComponent();
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
        }
    },

    methods: {
        createdComponent() {
            this.setIsLoading(true);
            this.emitOnChildRouteReadyChanged(false);

            this.profileStore.getList({
                aggregations: [
                    {
                        name: 'profileAgg',
                        field: 'name',
                        type: 'value'
                    }
                ]
            }).then((profiles) => {
                this.profiles = profiles.aggregations.profileAgg;

                const params = {
                    offset: 0,
                    limit: 1
                };
                this.generalSettingStore.getList(params).then((response) => {
                    if (!response || response.items[0].selectedProfileId === null) {
                        this.setIsLoading(false);
                        return;
                    }

                    this.profileStore.getByIdAsync(response.items[0].selectedProfileId).then((profileResponse) => {
                        if (profileResponse.id === null) {
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
                        aggregations: [
                            {
                                name: 'gatewayAgg',
                                type: 'value',
                                field: 'gatewayName'
                            }
                        ]
                    }).then((profiles) => {
                        this.gateways = profiles.aggregations.gatewayAgg;
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
                    criteria: criteria
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
