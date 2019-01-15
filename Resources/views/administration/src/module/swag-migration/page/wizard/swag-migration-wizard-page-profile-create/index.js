import { Component, State } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import template from './swag-migration-wizard-page-profile-create.html.twig';

Component.register('swag-migration-wizard-page-profile-create', {
    template,

    created() {
        this.createdComponent();
    },

    data() {
        return {
            isLoading: true,
            selection: {
                profile: null,
                gateway: null
            },
            profiles: [],
            gateways: []
        };
    },

    computed: {
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
                        field: 'profile',
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

                        this.selection.profile = profileResponse.profile;
                        this.onSelectProfile().then(() => {
                            this.selection.gateway = profileResponse.gateway;
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
                    const criteria = CriteriaFactory.equals('profile', this.selection.profile);

                    this.profileStore.getList({
                        criteria: criteria,
                        aggregations: [
                            {
                                name: 'gatewayAgg',
                                type: 'value',
                                field: 'gateway'
                            }
                        ]
                    }).then((profiles) => {
                        this.gateways = profiles.aggregations.gatewayAgg;
                        this.selection.gateway = null;
                        this.emitOnChildRouteReadyChanged(false);
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
                    CriteriaFactory.equals('profile', this.selection.profile),
                    CriteriaFactory.equals('gateway', this.selection.gateway)
                );

                this.profileStore.getList({
                    criteria: criteria
                }).then((profile) => {
                    if (profile.total !== 0) {
                        this.$emit('onProfileSelected', profile.items[0]);
                        this.emitOnChildRouteReadyChanged(true);
                    }
                    resolve();
                });
            });
        },

        emitOnChildRouteReadyChanged(isReady) {
            this.$emit('onChildRouteReadyChanged', isReady);
        }
    }
});
