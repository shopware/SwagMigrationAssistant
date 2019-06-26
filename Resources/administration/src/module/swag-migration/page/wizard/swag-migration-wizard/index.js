import { Component, Mixin, State } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import template from './swag-migration-wizard.html.twig';
import './swag-migration-wizard.scss';


Component.register('swag-migration-wizard', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService'
    },

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
                    index: 0,
                    titleSnippet: 'swag-migration.wizard.pages.introduction.title'
                },
                connectionCreate: {
                    name: 'swag.migration.wizard.connectionCreate',
                    index: 0.1, // not available through nextRoute (child from profile)
                    titleSnippet: 'swag-migration.wizard.pages.connectionCreate.title'
                },
                connectionSelect: {
                    name: 'swag.migration.wizard.connectionSelect',
                    index: 0.1, // not available through nextRoute (child from profile)
                    titleSnippet: 'swag-migration.wizard.pages.connectionSelect.title'
                },
                profileInformation: {
                    name: 'swag.migration.wizard.profileInformation',
                    index: 1,
                    titleSnippet: 'swag-migration.wizard.pages.profileInformation.title'
                },
                credentials: {
                    name: 'swag.migration.wizard.credentials',
                    index: 2,
                    titleSnippet: 'swag-migration.wizard.pages.credentials.title'
                },
                credentialsSuccess: {
                    name: 'swag.migration.wizard.credentialsSuccess',
                    index: 2.1, // not available through nextRoute (child from credentials)
                    titleSnippet: 'swag-migration.wizard.pages.credentials.statusTitle'
                },
                credentialsError: {
                    name: 'swag.migration.wizard.credentialsError',
                    index: 2.1, // not available through nextRoute (child from credentials)
                    titleSnippet: 'swag-migration.wizard.pages.credentials.statusTitle'
                }
            },
            profile: {}, // state object
            connection: {},
            connectionName: '',
            selectedProfile: {},
            childRouteReady: false, // child routes with forms will emit and change this value depending on their validation.
            errorMessageSnippet: '',
            migrationProcessStore: State.getStore('migrationProcess'),
            migrationUIStore: State.getStore('migrationUI'),
            connectionNameErrorSnippet: ''
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        modalSize() {
            if ([
                this.routes.credentialsSuccess,
                this.routes.credentialsError
            ].includes(this.currentRoute)) {
                return '460px';
            }

            return '688px';
        },

        modalTitleSnippet() {
            return this.currentRoute.titleSnippet;
        },

        buttonBackSnippet() {
            return 'swag-migration.wizard.buttonToProfileInformation';
        },

        buttonBackVisible() {
            return (
                !this.isLoading &&
                this.currentRoute === this.routes.credentials &&
                this.profileInformationComponentIsLoaded
            );
        },

        buttonSecondarySnippet() {
            if (this.currentRoute === this.routes.credentialsError) {
                return 'swag-migration.wizard.buttonLater';
            }

            return 'swag-migration.wizard.buttonAbort';
        },

        buttonSecondaryVisible() {
            return (this.currentRoute !== this.routes.credentialsSuccess);
        },

        buttonPrimarySnippet() {
            if (this.currentRoute === this.routes.introduction) {
                return 'swag-migration.wizard.buttonLetsGo';
            }

            if (this.currentRoute === this.routes.connectionCreate) {
                return 'swag-migration.wizard.buttonConnectionCreate';
            }

            if (this.currentRoute === this.routes.connectionSelect) {
                return 'swag-migration.wizard.buttonConnectionSelect';
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
                return !this.childRouteReady || this.isLoading;
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
            this.loadSelectedConnection(this.$route.params.connectionId).then(() => {
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
            this.isLoading = true;
            this.migrationService.checkConnection(this.connection.id).then((connectionCheckResponse) => {
                this.migrationProcessStore.setConnectionId(this.connection.id);
                this.migrationProcessStore.setEntityGroups([]);
                this.migrationProcessStore.setErrors([]);
                this.isLoading = false;

                if (!connectionCheckResponse) {
                    this.onResponseError(-1);
                    return;
                }
                this.migrationProcessStore.setEnvironmentInformation(connectionCheckResponse);
                this.migrationUIStore.setDataSelectionIds([]);
                this.migrationUIStore.setPremapping([]);
                this.migrationUIStore.setDataSelectionTableData([]);

                if (connectionCheckResponse.requestStatus.code !== undefined) {
                    if (
                        connectionCheckResponse.requestStatus.code !== '' &&
                        connectionCheckResponse.requestStatus.isWarning === false
                    ) {
                        this.onResponseError(connectionCheckResponse.requestStatus.code);
                        return;
                    }

                    // create warning for success page
                    this.errorMessageSnippet = '';
                    if (
                        connectionCheckResponse.requestStatus.code !== '' &&
                        connectionCheckResponse.requestStatus.isWarning === true
                    ) {
                        this.errorMessageSnippet =
                            `swag-migration.wizard.pages.credentials.success.${connectionCheckResponse.requestStatus.code}`;
                    }
                }

                this.navigateToRoute(this.routes.credentialsSuccess);
            }).catch((error) => {
                this.isLoading = false;
                this.migrationProcessStore.setConnectionId(this.connection.id);
                this.migrationProcessStore.setEntityGroups([]);
                this.migrationProcessStore.setErrors([]);
                this.migrationProcessStore.setEnvironmentInformation({});
                this.migrationUIStore.setDataSelectionIds([]);
                this.migrationUIStore.setPremapping([]);
                this.migrationUIStore.setDataSelectionTableData([]);
                this.onResponseError(error.response.data.errors[0].code);
            });
        },

        onResponseError(errorCode) {
            if (errorCode !== '') {
                this.errorMessageSnippet = `swag-migration.wizard.pages.credentials.error.${errorCode}`;
            } else {
                this.errorMessageSnippet = '';
            }

            if (this.errorMessageSnippet === this.$tc(`swag-migration.wizard.pages.credentials.error.${errorCode}`)) {
                this.errorMessageSnippet = 'swag-migration.wizard.pages.credentials.error.undefinedErrorMsg';
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
            if ([
                this.routes.credentialsSuccess,
                this.routes.credentialsError
            ].includes(this.currentRoute)) {
                this.$nextTick(() => {
                    this.$refs.primaryButton.$el.focus();
                });
            } else {
                this.$refs.primaryButton.$el.focus();
            }
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
                this.routes.profileInformation.index = 0.1;
            }
        },

        onButtonBackClick() {
            this.navigateToRoute(this.routes.profileInformation);
        },

        onButtonSecondaryClick() {
            // Abort / Later
            this.onCloseModal();
        },

        triggerPrimaryClick() {
            if (!this.buttonPrimaryDisabled) {
                this.onButtonPrimaryClick();
            }
        },

        onButtonPrimaryClick() {
            if (this.currentRoute === this.routes.connectionCreate) {
                // clicked Next (save selected profile)
                this.createNewConnection().then(() => {
                    this.navigateToNext();
                }).catch(() => {
                    this.connectionNameErrorSnippet =
                        'swag-migration.wizard.pages.connectionCreate.connectionNameExistsError';
                    this.isLoading = false;
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

        loadSelectedConnection(connectionId) {
            return new Promise((resolve) => {
                // resolve if connection is already loaded
                if (Object.keys(this.connection).length) {
                    resolve();
                    return;
                }

                this.isLoading = true;

                if (connectionId !== undefined) {
                    this.fetchConnection(connectionId).then(() => {
                        resolve();
                    });
                    return;
                }

                this.migrationGeneralSettingStore.getList({ limit: 1 }).then((response) => {
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

                    this.fetchConnection(response.items[0].selectedConnectionId);
                });
            });
        },

        fetchConnection(connectionId) {
            return new Promise((resolve) => {
                const params = {
                    limit: 1,
                    criteria: CriteriaFactory.equals('id', connectionId)
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
            return this.checkConnectionName(this.connectionName).then((valid) => {
                if (!valid) {
                    this.isLoading = false;
                    return Promise.reject();
                }

                this.connectionNameErrorSnippet = '';
                const newConnection = this.migrationConnectionStore.create();
                newConnection.profileId = this.selectedProfile.id;
                newConnection.name = this.connectionName;
                return newConnection.save().then((savedConnection) => {
                    return this.saveSelectedConnection(savedConnection);
                });
            });
        },

        checkConnectionName(name) {
            return this.migrationConnectionStore.getList({
                criteria: CriteriaFactory.equals('name', name)
            }).then((res) => {
                return res.items.length === 0;
            });
        },

        saveSelectedConnection(connection) {
            return new Promise((resolve, reject) => {
                this.isLoading = true;

                this.migrationGeneralSettingStore.getList({ limit: 1 }).then((response) => {
                    if (!response ||
                        (response && response.items.length < 1)
                    ) {
                        this.isLoading = false;
                        reject();
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
                        reject();
                    });
                }).catch(() => {
                    this.isLoading = false;
                    reject();
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
            if (this.connectionName !== null && this.connectionName.length > 5) {
                this.connectionNameErrorSnippet = '';
                return;
            }

            this.connectionNameErrorSnippet = 'swag-migration.wizard.pages.connectionCreate.connectionNameTooShort';
        },

        onChildIsLoadingChanged(value) {
            this.childIsLoading = value;
        },

        onConnectionSelected(value) {
            this.connection = value;
        }
    }
});
