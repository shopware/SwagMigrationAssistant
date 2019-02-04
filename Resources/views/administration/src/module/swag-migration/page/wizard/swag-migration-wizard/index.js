import { Component, Mixin, State } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import template from './swag-migration-wizard.html.twig';
import './swag-migration-wizard.less';


const PROFILE_SELECTION = Object.freeze({
    CREATE: 'create-profile',
    SELECT: 'select-profile'
});

Component.register('swag-migration-wizard', {
    template,

    inject: ['migrationService'],

    mixins: [
        Mixin.getByName('swag-wizard')
    ],

    data() {
        return {
            showModal: true,
            isLoading: true,
            childIsLoading: false,
            routes: {
                introduction: {
                    name: 'swag.migration.wizard.introduction',
                    index: 0
                },
                profile: {
                    name: 'swag.migration.wizard.profile',
                    index: 1
                },
                profileCreate: {
                    name: 'swag.migration.wizard.profileCreate',
                    index: 1.1 // not available through nextRoute (child from profile)
                },
                profileSelect: {
                    name: 'swag.migration.wizard.profileSelect',
                    index: 1.1 // not available through nextRoute (child from profile)
                },
                profileInformation: {
                    name: 'swag.migration.wizard.profileInformation',
                    index: 2
                },
                credentials: {
                    name: 'swag.migration.wizard.credentials',
                    index: 3
                },
                credentialsSuccess: {
                    name: 'swag.migration.wizard.credentialsSuccess',
                    index: 3.1 // not available through nextRoute (child from credentials)
                },
                credentialsError: {
                    name: 'swag.migration.wizard.credentialsError',
                    index: 3.1 // not available through nextRoute (child from credentials)
                }
            },
            buttonArrowVisible: true,
            buttonSecondaryVisible: true,
            profileSelection: 'create-profile',
            profile: {}, // state object
            selectedProfile: {},
            childRouteReady: false, // child routes with forms will emit and change this value depending on their validation.
            errorMessageSnippet: ''
        };
    },

    computed: {
        buttonArrowSnippet() {
            if ([
                this.routes.profileCreate,
                this.routes.profileSelect
            ].includes(this.currentRoute)) {
                return 'swag-migration.wizard.buttonToStart';
            }

            return 'swag-migration.wizard.buttonToProfiles';
        },

        buttonSecondarySnippet() {
            if (this.currentRoute === this.routes.credentialsError) {
                return 'swag-migration.wizard.buttonLater';
            }

            return 'swag-migration.wizard.buttonAbort';
        },

        buttonPrimarySnippet() {
            if (this.currentRoute === this.routes.introduction) {
                return 'swag-migration.wizard.buttonLetsGo';
            }

            if (this.currentRoute === this.routes.credentials) {
                return 'swag-migration.wizard.buttonConnect';
            }

            if (this.currentRoute === this.routes.credentialsSuccess) {
                return 'swag-migration.wizard.buttonFinish';
            }

            if (this.currentRoute === this.routes.credentialsError) {
                return 'swag-migration.wizard.buttonEdit';
            }

            return 'swag-migration.wizard.buttonNext';
        },

        buttonPrimaryDisabled() {
            // TODO: Remove this if the profile selection works.
            if (this.currentRoute === this.routes.profileSelect) {
                return true;
            }

            if ([
                this.routes.credentials,
                this.routes.profileCreate
            ].includes(this.currentRoute)) {
                return !this.childRouteReady;
            }

            return this.isLoading;
        },

        migrationProfileStore() {
            return State.getStore('swag_migration_profile');
        },

        migrationGeneralSettingStore() {
            return State.getStore('swag_migration_general_setting');
        },

        profileInformationComponent() {
            return `swag-migration-profile-${this.profile.profile}-${this.profile.gateway}-page-information`;
        },

        profileInformationComponentIsLoaded() {
            return Component.getComponentRegistry().has(this.profileInformationComponent);
        },

        credentialsComponent() {
            return `swag-migration-profile-${this.profile.profile}-${this.profile.gateway}-credential-form`;
        }
    },

    created() {
        this.createdComponent();
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
            // check for non empty given profile
            if (this.$route.params.profile !== undefined && Object.keys(this.$route.params.profile).length) {
                this.profile = this.$route.params.profile;
                this.onChildRouteChanged(); // update strings for current child
                this.isLoading = false;
                return;
            }

            this.loadSelectedProfile().then(() => {
                this.onChildRouteChanged(); // update strings for current child
                this.isLoading = false;
            });
        },

        /**
         * Remove any whitespaces before or after the strings in the credentials object.
         */
        trimCredentials() {
            Object.keys(this.profile.credentialFields).forEach((field) => {
                this.profile.credentialFields[field] = this.profile.credentialFields[field].trim();
            });
        },

        onConnect() {
            this.isLoading = true;
            this.buttonArrowVisible = false;
            this.errorMessageSnippet = '';

            this.trimCredentials();
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
                            this.errorMessageSnippet = '';
                            if (connectionCheckResponse.warningCode === 0) {
                                this.errorMessageSnippet =
                                    'swag-migration.wizard.pages.credentials.success.connectionInsecureMsg';
                            }
                        }

                        this.navigateToRoute(this.routes.credentialsSuccess);
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
                this.errorMessageSnippet = 'swag-migration.wizard.pages.credentials.error.connectionErrorMsg';
                break;
            case 401: // invalid access credentials
                this.errorMessageSnippet = 'swag-migration.wizard.pages.credentials.error.authenticationErrorMsg';
                break;
            case 466: // invalid shop domain
                this.errorMessageSnippet = 'swag-migration.wizard.pages.credentials.error.invalidShopDomainErrorMsg';
                break;
            default: // something else
                this.errorMessageSnippet = 'swag-migration.wizard.pages.credentials.error.undefinedErrorMsg';
                break;
            }

            this.navigateToRoute(this.routes.credentialsError);
        },

        onCloseModal() {
            this.showModal = false;

            // navigate depending on the current state
            if (Object.keys(this.profile).length) {
                // navigate to module
                this.$router.push({
                    name: 'swag.migration.index',
                    params: { profileId: this.profile.id }
                });

                return;
            }

            this.$router.push({
                name: 'swag.migration.emptyScreen'
            });
        },

        onChildRouteChanged() {
            this.checkForDisabledRoute();

            // Handle arrow button visibility
            this.buttonArrowVisible = ![
                this.routes.introduction,
                this.routes.profile,
                this.routes.credentialsSuccess,
                this.routes.credentialsError
            ].includes(this.currentRoute);

            // Handle secondary button visibility
            this.buttonSecondaryVisible = (this.currentRoute !== this.routes.credentialsSuccess);
        },

        checkForDisabledRoute() {
            if (!Object.keys(this.profile).length) {
                // there is no profile selected. redirect to the selection
                this.onNoProfileSelected();
                return;
            }

            if (!this.profileInformationComponentIsLoaded) {
                if (this.currentRoute === this.routes.profileInformation) {
                    this.navigateToRoute(this.routes.credentials);
                }

                // make the profileInformation route a child if there is no component
                // so navigation to this route is not possible for the user
                this.routes.profileInformation.index = 1.1;
            }
        },

        onButtonArrowClick() {
            if ([
                this.routes.profileCreate,
                this.routes.profileSelect
            ].includes(this.currentRoute)) {
                this.navigateToRoute(this.routes.profile);
                return;
            }

            this.navigateToRoute(this.routes.profileCreate);
        },

        onButtonSecondaryClick() {
            // Abort / Later
            this.onCloseModal();
        },

        onButtonPrimaryClick() {
            if (this.currentRoute === this.routes.profile) {
                // depending on the selection navigate to the right route.
                if (this.profileSelection === PROFILE_SELECTION.CREATE) {
                    this.navigateToRoute(this.routes.profileCreate);
                    return;
                }
                this.navigateToRoute(this.routes.profileSelect);
                return;
            }

            if (this.currentRoute === this.routes.profileCreate) {
                // clicked Next (save selected profile)
                this.saveSelectedProfile().then(() => {
                    this.navigateToNext();
                });
                return;
            }

            if (this.currentRoute === this.routes.credentials) {
                // clicked Connect.
                this.onConnect();
                return;
            }

            if (this.currentRoute === this.routes.credentialsSuccess) {
                // clicked Finish.
                this.onCloseModal();
                return;
            }

            if (this.currentRoute === this.routes.credentialsError) {
                // clicked Edit
                this.navigateToRoute(this.routes.credentials);
                return;
            }

            this.navigateToNext();
        },

        loadSelectedProfile() {
            return new Promise((resolve) => {
                // resolve if profile is already loaded
                if (Object.keys(this.profile).length) {
                    resolve();
                    return;
                }

                this.isLoading = true;
                let params = {
                    offset: 0,
                    limit: 1
                };

                this.migrationGeneralSettingStore.getList(params).then((response) => {
                    if (!response) {
                        this.isLoading = false;
                        this.onNoProfileSelected();
                        resolve();
                        return;
                    }

                    if (response.items[0].selectedProfileId === null) {
                        this.isLoading = false;
                        this.onNoProfileSelected();
                        resolve();
                        return;
                    }

                    params = {
                        offset: 0,
                        limit: 1,
                        criteria: CriteriaFactory.equals('id', response.items[0].selectedProfileId)
                    };
                    this.migrationProfileStore.getList(params).then((profileResponse) => {
                        if (profileResponse.items[0].id === null) {
                            this.isLoading = false;
                            this.onNoProfileSelected();
                            resolve();
                            return;
                        }

                        this.profile = profileResponse.items[0];
                        this.isLoading = false;
                        resolve();
                    });
                });
            });
        },

        onNoProfileSelected() {
            if ([
                this.routes.profileInformation,
                this.routes.credentials,
                this.routes.credentialsSuccess,
                this.routes.credentialsError
            ].includes(this.currentRoute)) {
                this.navigateToRoute(this.routes.profile);
            }
        },

        saveSelectedProfile() {
            return new Promise((resolve) => {
                this.isLoading = true;

                const params = {
                    offset: 0,
                    limit: 1
                };

                this.migrationGeneralSettingStore.getList(params).then((response) => {
                    if (!response ||
                        (response && response.items.length < 1)
                    ) {
                        this.isLoading = false;
                        resolve();
                        return;
                    }

                    const setting = response.items[0];
                    setting.selectedProfileId = this.selectedProfile.id;
                    setting.save().then(() => {
                        this.profile = this.selectedProfile;
                        this.isLoading = false;
                        resolve();
                    }).catch(() => {
                        this.isLoading = false;
                        resolve();
                    });
                }).catch(() => {
                    this.isLoading = false;
                    resolve();
                });
            });
        },

        onChildRouteReadyChanged(value) {
            this.childRouteReady = value;
        },

        onCredentialsChanged(value) {
            this.profile.credentialFields = value;
        },

        onProfileSelected(value) {
            this.selectedProfile = value;
        },

        onChildIsLoadingChanged(value) {
            this.childIsLoading = value;
        },

        onProfileSelectionChanged(value) {
            this.profileSelection = value;
        }
    }
});
