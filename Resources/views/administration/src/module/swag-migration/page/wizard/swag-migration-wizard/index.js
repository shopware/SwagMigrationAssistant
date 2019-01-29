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
                chooseAction: {
                    name: 'swag.migration.wizard.chooseAction',
                    index: 1
                },
                connectionCreate: {
                    name: 'swag.migration.wizard.connectionCreate',
                    index: 1.1 // not available through nextRoute (child from profile)
                },
                connectionSelect: {
                    name: 'swag.migration.wizard.connectionSelect',
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
            connection: {},
            connectionName: '',
            selectedProfile: {},
            childRouteReady: false, // child routes with forms will emit and change this value depending on their validation.
            errorMessageSnippet: ''
        };
    },

    computed: {
        buttonArrowSnippet() {
            return 'swag-migration.wizard.buttonToStart';
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
            if ([
                this.routes.credentials,
                this.routes.connectionCreate,
                this.routes.connectionSelect
            ].includes(this.currentRoute)) {
                return !this.childRouteReady;
            }

            return this.isLoading;
        },

        migrationConnectionStore() {
            return State.getStore('swag_migration_connection');
        },

        migrationProfileStore() {
            return State.getStore('swag_migration_profile');
        },

        migrationGeneralSettingStore() {
            return State.getStore('swag_migration_general_setting');
        },

        profileInformationComponent() {
            if (!this.connection || !this.connection.profile) {
                return '';
            }

            return `swag-migration-profile-${this.connection.profile.name}-` +
            `${this.connection.profile.gatewayName}-page-information`;
        },

        profileInformationComponentIsLoaded() {
            return Component.getComponentRegistry().has(this.profileInformationComponent);
        },

        credentialsComponent() {
            if (!this.connection || !this.connection.profile) {
                return '';
            }

            return `swag-migration-profile-${this.connection.profile.name}-` +
                `${this.connection.profile.gatewayName}-credential-form`;
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
            // check for non empty given connection
            if (this.$route.params.connection !== undefined && Object.keys(this.$route.params.connection).length) {
                this.connection = this.$route.params.connection;
                this.onChildRouteChanged(); // update strings for current child
                this.isLoading = false;
                return;
            }

            this.loadSelectedConnection().then(() => {
                this.onChildRouteChanged(); // update strings for current child
                this.isLoading = false;
            });
        },

        /**
         * Remove any whitespaces before or after the strings in the credentials object.
         */
        trimCredentials() {
            Object.keys(this.connection.credentialFields).forEach((field) => {
                this.connection.credentialFields[field] = this.connection.credentialFields[field].trim();
            });
        },

        onConnect() {
            this.isLoading = true;
            this.buttonArrowVisible = false;
            this.errorMessageSnippet = '';

            this.trimCredentials();
            this.migrationService.updateConnectionCredentials(
                this.connection.id,
                this.connection.credentialFields
            ).then((response) => {
                if (response.errors && response.errors.length > 0) {
                    this.isLoading = false;
                    this.onResponseError('');
                }

                this.doConnectionCheck();
            }).catch((error) => {
                this.isLoading = false;
                this.onResponseError(error.response.data.errors[0].code);
            });
        },

        doConnectionCheck() {
            this.migrationService.checkConnection(this.connection.id).then((connectionCheckResponse) => {
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
            case 'SWAG-MIGRATION-CONNECTION-CREDENTIALS-MISSING':
                this.errorMessageSnippet = 'swag-migration.wizard.pages.credentials.error.credentialsMissing';
                break;
            case 'SWAG-MIGRATION-IS-RUNNING':
                this.errorMessageSnippet = 'swag-migration.wizard.pages.credentials.error.migrationRunning';
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
            if (Object.keys(this.connection).length) {
                // navigate to module
                this.$router.push({
                    name: 'swag.migration.index',
                    params: { connectionId: this.connection.id }
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
                this.routes.chooseAction,
                this.routes.credentialsSuccess,
                this.routes.credentialsError
            ].includes(this.currentRoute);

            // Handle secondary button visibility
            this.buttonSecondaryVisible = (this.currentRoute !== this.routes.credentialsSuccess);
        },

        checkForDisabledRoute() {
            if (!Object.keys(this.connection).length) {
                // there is no connection selected. redirect to the selection
                this.onNoConnectionSelected();
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
            this.navigateToRoute(this.routes.chooseAction);
        },

        onButtonSecondaryClick() {
            // Abort / Later
            this.onCloseModal();
        },

        onButtonPrimaryClick() {
            if (this.currentRoute === this.routes.chooseAction) {
                // depending on the selection navigate to the right route.
                if (this.profileSelection === PROFILE_SELECTION.CREATE) {
                    this.navigateToRoute(this.routes.connectionCreate);
                    return;
                }
                this.navigateToRoute(this.routes.connectionSelect);
                return;
            }

            if (this.currentRoute === this.routes.connectionCreate) {
                // clicked Next (save selected profile)
                this.createNewConnection().then(() => {
                    this.navigateToNext();
                });
                return;
            }

            if (this.currentRoute === this.routes.connectionSelect) {
                this.saveSelectedConnection(this.connection).then(() => {
                    this.doConnectionCheck();
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

        loadSelectedConnection() {
            return new Promise((resolve) => {
                // resolve if connection is already loaded
                if (Object.keys(this.connection).length) {
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
                        this.onNoConnectionSelected();
                        resolve();
                        return;
                    }

                    if (response.items[0].selectedConnectionId === null) {
                        this.isLoading = false;
                        this.onNoConnectionSelected();
                        resolve();
                        return;
                    }

                    params = {
                        offset: 0,
                        limit: 1,
                        criteria: CriteriaFactory.equals('id', response.items[0].selectedConnectionId)
                    };
                    this.migrationConnectionStore.getList(params).then((connectionResponse) => {
                        if (connectionResponse.items[0].id === null) {
                            this.isLoading = false;
                            this.onNoConnectionSelected();
                            resolve();
                            return;
                        }

                        this.connection = connectionResponse.items[0];
                        this.isLoading = false;
                        resolve();
                    });
                });
            });
        },

        onNoConnectionSelected() {
            if ([
                this.routes.chooseAction,
                this.routes.profileInformation,
                this.routes.credentials,
                this.routes.credentialsSuccess,
                this.routes.credentialsError
            ].includes(this.currentRoute)) {
                this.navigateToRoute(this.routes.connectionCreate);
            }
        },

        createNewConnection() {
            this.isLoading = true;

            const newConnection = this.migrationConnectionStore.create();
            newConnection.profileId = this.selectedProfile.id;
            newConnection.name = this.connectionName;
            newConnection.save();

            return this.saveSelectedConnection(newConnection);
        },

        saveSelectedConnection(connection) {
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
                    setting.selectedConnectionId = connection.id;
                    setting.save().then(() => {
                        this.connection = connection;
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
            this.connection.credentialFields = value;
        },

        onProfileSelected(value) {
            this.selectedProfile = value;
        },

        onChangeConnectionName(value) {
            this.connectionName = value;
        },

        onChildIsLoadingChanged(value) {
            this.childIsLoading = value;
        },

        onProfileSelectionChanged(value) {
            this.profileSelection = value;
        },

        onConnectionSelected(value) {
            this.connection = value;
        }
    }
});
