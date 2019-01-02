import { Component, State } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import template from './swag-migration-wizard.html.twig';
import './swag-migration-wizard.less';

Component.register('swag-migration-wizard', {
    template,

    inject: ['migrationService'],

    data() {
        return {
            showModal: true,
            isLoading: true,
            editMode: false, // Don't show dot navigation or back button if we come from the module
            buttonPreviousVisible: true,
            buttonNextVisible: true,
            buttonPreviousText: this.$tc('swag-migration.wizard.buttonPrev'),
            buttonNextText: this.$tc('swag-migration.wizard.buttonNext'),
            routes: [
                'swag.migration.wizard.introduction',
                'swag.migration.wizard.plugin_information',
                'swag.migration.wizard.select_profile',
                'swag.migration.wizard.credentials',
                'swag.migration.wizard.credentials_success',
                'swag.migration.wizard.credentials_error'
            ],
            routeCountVisible: 4, // only show 4 dots and allow navigation between them.
            routeIndex: 0,
            routeIndexVisible: 0, // only count up to 3
            profile: {}, // state object
            profileId: '',
            credentials: {},
            credentialsValid: false,
            profileSelectionValid: false,
            errorMessage: ''
        };
    },

    computed: {
        routeCount() {
            return this.routes.length;
        },

        routeSelectProfileIndex() {
            return 2;
        },

        routeCredentialsIndex() {
            return 3;
        },

        routeSuccessIndex() {
            return 4;
        },

        routeErrorIndex() {
            return 5;
        },

        nextButtonDisabled() {
            if (this.isLoading) {
                return true;
            }

            if (this.routeIndex === this.routeCredentialsIndex) {
                return !this.credentialsValid;
            }

            if (this.routeIndex === this.routeSelectProfileIndex) {
                return !this.profileSelectionValid;
            }

            return false;
        },

        backButtonDisabled() {
            return this.isLoading;
        },

        migrationProfileStore() {
            return State.getStore('swag_migration_profile');
        },

        migrationGeneralSettingStore() {
            return State.getStore('swag_migration_general_setting');
        }
    },

    created() {
        this.createdComponent();
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
        createdComponent() {
            this.editMode = this.$route.params.editMode !== undefined ? this.$route.params.editMode : false;
            this.matchRouteWithIndex();
            this.isLoading = false;
        },

        /**
         * Remove any whitespaces before or after the strings in the credentials object.
         */
        trimCredentials() {
            Object.keys(this.credentials).forEach((field) => {
                this.credentials[field] = this.credentials[field].trim();
            });
        },

        onConnect() {
            this.isLoading = true;
            this.errorMessage = '';

            this.trimCredentials();
            this.profile.credentialFields = this.credentials;
            this.profile.save().then((response) => {
                if (response.errors.length === 0) {
                    this.migrationService.checkConnection(this.profile.id).then((connectionCheckResponse) => {
                        this.isLoading = false;

                        if (!connectionCheckResponse) {
                            this.onResponseError(-1);
                            return;
                        }

                        if (connectionCheckResponse.errorCode !== undefined) {
                            if (connectionCheckResponse.errorCode !== -1) {
                                this.onResponseError(connectionCheckResponse.errorCode);
                                return;
                            }

                            // create warning for success page
                            this.errorMessage = '';
                            if (connectionCheckResponse.warningCode === 0) {
                                this.errorMessage = this.$tc(
                                    'swag-migration.wizard.pages.credentials.success.connectionInsecureMsg'
                                );
                            }
                        }

                        this.navigateToRoute(this.routes[this.routeSuccessIndex]);
                    }).catch((error) => {
                        this.isLoading = false;
                        this.onResponseError(error.response.data.errors[0].code);
                    });
                } else {
                    this.isLoading = false;
                    this.onResponseError('');
                }
            });
        },

        onResponseError(errorCode) {
            switch (errorCode) {
            case 404:
            case 0: // can't connect to shop
                this.errorMessage = this.$tc('swag-migration.wizard.pages.credentials.error.connectionErrorMsg');
                break;
            case 401: // invalid access credentials
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

            this.showModal = false;
            this.routeIndex = 0;
            this.routeIndexVisible = 0;

            // navigate to module
            this.$router.push({
                name: 'swag.migration.index',
                params: { profileId: this.profileId }
            });
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
            if (this.routeIndex === this.routeCredentialsIndex) {
                this.loadSelectedProfile();
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
            } else if (!this.editMode) {
                this.buttonPreviousVisible = this.routeIndex !== 0;
            } else if (this.routeIndex === this.routeCredentialsIndex) {
                this.buttonPreviousVisible = true;
            } else {
                this.buttonPreviousVisible = false;
            }
        },

        loadSelectedProfile() {
            this.credentialsValid = false;

            // check for empty profile
            if (!Object.keys(this.profile).length) {
                let params = {
                    offset: 0,
                    limit: 1
                };

                this.migrationGeneralSettingStore.getList(params).then((response) => {
                    if (!response) {
                        this.navigateToRoute(this.routes[this.routeSelectProfileIndex]);
                        return;
                    }

                    if (response.items[0].selectedProfileId === null) {
                        this.navigateToRoute(this.routes[this.routeSelectProfileIndex]);
                        return;
                    }

                    params = {
                        offset: 0,
                        limit: 1,
                        criteria: CriteriaFactory.equals('id', response.items[0].selectedProfileId)
                    };
                    this.migrationProfileStore.getList(params).then((profileResponse) => {
                        if (profileResponse.items[0].id === null) {
                            this.navigateToRoute(this.routes[this.routeSelectProfileIndex]);
                            return;
                        }

                        this.profile = profileResponse.items[0];
                        this.credentials = profileResponse.items[0].credentialFields;
                        this.profileId = profileResponse.items[0].id;
                        this.isLoading = false;
                    });
                });
            }
        },

        navigateToRoute(routeName) {
            this.$router.push({ name: routeName });
        },

        updateChildRoute() {
            this.navigateToRoute(this.routes[this.routeIndex]);
        },

        onPrevious() {
            if (this.routeIndex > 0) {
                this.routeIndex -= 1;
                this.routeIndexVisible -= 1;
                this.updateChildRoute();
            }
        },

        onNext() {
            if (this.routeIndex === this.routeCredentialsIndex) {
                // we clicked connect.
                this.onConnect();
                return;
            }

            if (this.routeIndex === this.routeSuccessIndex) {
                // we clicked finish.
                this.onCloseModal();
                return;
            }

            if (this.routeIndex === this.routeErrorIndex) {
                // we clicked Back
                this.navigateToRoute(this.routes[this.routeCredentialsIndex]);
                return;
            }

            if (this.routeIndex < this.routeCount - 1) {
                this.routeIndex += 1;
                this.routeIndexVisible += 1;
                this.updateChildRoute();
            }
        },

        onCredentialsChanged(value) {
            this.credentials = value;
        },

        onCredentialsValidationChanged(value) {
            this.credentialsValid = value;
        },

        onProfileSelected(value) {
            this.profile = value;
        },

        onProfileSelectionValidationChanged(value) {
            this.profileSelectionValid = value;
        },

        onIsLoadingChanged(value) {
            this.isLoading = value;
        }
    }
});
