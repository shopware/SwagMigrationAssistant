import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard.html.twig';
import './swag-migration-wizard.less';

Component.register('swag-migration-wizard', {
    template,

    inject: ['migrationProfileService', 'migrationService'],

    data() {
        return {
            showModal: true,
            isLoading: true,
            buttonPreviousVisible: true,
            buttonNextVisible: true,
            buttonPreviousText: this.$tc('swag-migration.wizard.buttonPrev'),
            buttonNextText: this.$tc('swag-migration.wizard.buttonNext'),
            routes: [
                'swag.migration.wizard.introduction',
                'swag.migration.wizard.plugin_information',
                'swag.migration.wizard.credentials',
                'swag.migration.wizard.credentials_success',
                'swag.migration.wizard.credentials_error'
            ],
            routeCountVisible: 3, // only show 4 dots and allow navigation between them.
            routeIndex: 0,
            routeIndexVisible: 0, // only count up to 3
            profileId: '0x945f840058dc4e5583a02f70bef46071',
            credentials: {}, // .endpoint .apiUser .apiKey
            errorMessage: ''
        };
    },

    computed: {
        routeCount() {
            return this.routes.length;
        },
        routeApiCredentialsIndex() {
            return 2;
        },
        routeSuccessIndex() {
            return 3;
        },
        routeErrorIndex() {
            return 4;
        },
        nextButtonDisabled() {
            if (this.isLoading) {
                return true;
            }

            if (this.routeIndex === this.routeApiCredentialsIndex) {
                return !(this.credentials.endpoint && this.credentials.apiUser && this.credentials.apiKey);
            }

            return false;
        },
        backButtonDisabled() {
            return this.isLoading;
        }
    },

    created() {
        const params = {
            offset: 0,
            limit: 100,
            term: { gateway: 'api' }
        };

        this.migrationProfileService.getList(params).then((response) => {
            if (!response) {
                return;
            }

            this.credentials = response.data[0].credentialFields;
            this.profileId = response.data[0].id;
            this.isLoading = false;
        });

        this.matchRouteWithIndex();
    },

    beforeRouteUpdate(to, from, next) {
        next();
        this.matchRouteWithIndex();
    },

    /**
     * Close modal and after it is closed we redirect to next route.
     * (note: without closing it first the sw-modal will stay in the DOM)
     *
     * @param to
     * @param from
     * @param next
     */
    beforeRouteLeave(to, from, next) {
        this.showModal = false;
        this.$nextTick(() => {
            next();
        });
    },

    methods: {
        onConnect() {
            this.isLoading = true;

            this.migrationProfileService.updateById(
                this.profileId,
                { credentialFields: this.credentials }
            ).then((response) => {
                if (response.status === 204) {
                    this.migrationService.checkConnection(this.profileId).then((connectionCheckResponse) => {
                        this.isLoading = false;
                        if (connectionCheckResponse.environmentInformation) {
                            if (connectionCheckResponse.error) {
                                switch (connectionCheckResponse.error.code) {
                                case 0: // SSL certificate is not verified
                                    this.errorMessage = this.$tc(
                                        'swag-migration.wizard.pages.credentials.success.connectionUnsecureMsg'
                                    );
                                    break;
                                default: // Something else
                                    this.errorMessage = this.$tc(
                                        'swag-migration.wizard.pages.credentials.success.undefinedErrorMsg'
                                    );
                                    break;
                                }
                            }

                            this.navigateToRoute(this.routes[this.routeSuccessIndex]);
                        } else {
                            this.onResponseError(-1);
                        }
                    }).catch((error) => {
                        this.isLoading = false;
                        this.onResponseError(error.response.data.errors[0].code);
                    });
                } else {
                    this.isLoading = false;
                    this.onResponseError(response.status);
                }
            });
        },

        onResponseError(errorCode) {
            switch (errorCode) {
            case '0': // can't connect to shop
                this.errorMessage = this.$tc('swag-migration.wizard.pages.credentials.error.connectionErrorMsg');
                break;
            case '401': // invalid access credentials
                this.errorMessage = this.$tc('swag-migration.wizard.pages.credentials.error.authenticationErrorMsg');
                break;
            default: // something else
                this.errorMessage = this.$tc('swag-migration.wizard.pages.credentials.error.undefinedErrorMsg');
                break;
            }

            this.navigateToRoute(this.routes[this.routeErrorIndex]);
        },

        onCloseModal() {
            if (this.isLoading) {
                return;
            }

            const routeIndexCopy = this.routeIndex;

            this.showModal = false;
            this.routeIndex = 0;
            this.routeIndexVisible = 0;

            if (routeIndexCopy === this.routeSuccessIndex) {
                // navigate to module
                this.$router.push({
                    name: 'swag.migration.index',
                    params: { profileId: this.profileId }
                });
            }
        },

        matchRouteWithIndex() {
            // check for current child route
            const currentRouteIndex = this.routes.findIndex((r) => {
                return r === this.$router.currentRoute.name;
            });

            if (currentRouteIndex !== -1) {
                if (currentRouteIndex > this.routeCountVisible - 1) {
                    this.routeIndexVisible = this.routeCountVisible - 1;
                } else {
                    this.routeIndexVisible = currentRouteIndex;
                }

                this.routeIndex = currentRouteIndex;
                this.onChildRouteChanged();
            }
        },

        onChildRouteChanged() {
            this.buttonPreviousText = this.$tc('swag-migration.wizard.buttonPrev');

            // Handle next button text
            if (this.routeIndex === this.routeApiCredentialsIndex) {
                this.buttonNextText = this.$tc('swag-migration.wizard.buttonConnect');
            } else if (this.routeIndex === this.routeSuccessIndex) {
                this.buttonNextText = this.$tc('swag-migration.wizard.buttonFinish');
            } else if (this.routeIndex === this.routeErrorIndex) {
                this.buttonNextText = this.$tc('swag-migration.wizard.buttonPrev');
            } else {
                this.buttonNextText = this.$tc('swag-migration.wizard.buttonNext');
            }

            // Handle back button
            if (this.routeIndex === this.routeSuccessIndex || this.routeIndex === this.routeErrorIndex) {
                this.buttonPreviousVisible = false;
            } else {
                this.buttonPreviousVisible = this.routeIndex !== 0;
            }
        },

        navigateToRoute(routeName) {
            this.$router.push({ name: routeName });
        },

        updateChildRoute() {
            this.navigateToRoute(this.routes[this.routeIndex]);
            this.onChildRouteChanged();
        },

        onPrevious() {
            if (this.routeIndex > 0) {
                this.routeIndex -= 1;
                this.routeIndexVisible -= 1;
                this.updateChildRoute();
            }
        },

        onNext() {
            if (this.routeIndex === this.routeApiCredentialsIndex) {
                // we clicked connect.
                this.onConnect();
                return;
            } if (this.routeIndex === this.routeSuccessIndex) {
                // we clicked finish.
                this.onCloseModal();
                return;
            } if (this.routeIndex === this.routeErrorIndex) {
                // we clicked Back
                this.navigateToRoute(this.routes[this.routeApiCredentialsIndex]);
                return;
            }

            if (this.routeIndex < this.routeCount - 1) {
                this.routeIndex += 1;
                this.routeIndexVisible += 1;
                this.updateChildRoute();
            }
        },

        onApiKeyChanged(value) {
            this.credentials.apiKey = value;
        },

        onApiUserChanged(value) {
            this.credentials.apiUser = value;
        },

        onEndpointChanged(value) {
            this.credentials.endpoint = value;
        }
    }
});
