import template from './swag-migration-wizard.html.twig';
import './swag-migration-wizard.scss';
import { MIGRATION_STEP } from '../../../../../core/service/api/swag-migration.api.service';

const { Component, Mixin, State } = Shopware;
const { Criteria } = Shopware.Data;
const SSL_REQUIRED_ERROR_CODE = 'SWAG_MIGRATION__SSL_REQUIRED';
const { mapState } = Shopware.Component.getComponentHelper();

const CONNECTION_NAME_ERRORS = Object.freeze({
    NAME_TO_SHORT: 'SWAG_MIGRATION_CONNECTION_NAME_TO_SHORT',
    NAME_ALREADY_EXISTS: 'SWAG_MIGRATION_CONNECTION_NAME_ALREADY_EXISTS',
});

/**
 * @private
 * @package services-settings
 *
 * Note:
 * This component should not inherit from another component, because Rufus is overriding it and NEXT-36774 breaks it then.
 * We might inherit from 'swag-migration-base' in the future again.
 */
Component.register('swag-migration-wizard', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationApiService */
        migrationApiService: 'migrationApiService',
        repositoryFactory: 'repositoryFactory',
    },

    mixins: [
        Mixin.getByName('swag-wizard'),
    ],

    data() {
        const routes = this.getRoutes();

        return {
            context: Shopware.Context.api,
            storesInitializing: true,
            showModal: true,
            isLoading: true,
            childIsLoading: false,
            routes,
            connection: {},
            connectionName: '',
            selectedProfile: {},
            childRouteReady: false, // child routes with forms will emit and change this value depending on their validation.
            errorMessageSnippet: '',
            errorMessageHintSnippet: '',
            connectionNameErrorCode: '',
            currentErrorCode: '',
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        ...mapState('swagMigration', [
            'connectionId',
        ]),

        migrationConnectionRepository() {
            return this.repositoryFactory.create('swag_migration_connection');
        },

        migrationGeneralSettingRepository() {
            return this.repositoryFactory.create('swag_migration_general_setting');
        },

        modalSize() {
            if ([
                this.routes.credentialsSuccess,
                this.routes.credentialsError,
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
                if (this.currentErrorCode === SSL_REQUIRED_ERROR_CODE) {
                    return 'swag-migration.wizard.buttonUseSsl';
                }

                return 'swag-migration.wizard.buttonEdit';
            }

            return 'swag-migration.wizard.buttonNext';
        },

        buttonPrimaryDisabled() {
            if ([
                this.routes.credentials,
                this.routes.connectionCreate,
                this.routes.connectionSelect,
            ].includes(this.currentRoute)) {
                return !this.childRouteReady || this.isLoading;
            }

            return this.isLoading;
        },

        profileInformationComponent() {
            if (!this.connection || !this.connection.profileName || !this.connection.gatewayName) {
                return '';
            }

            return `swag-migration-profile-${this.connection.profileName}-` +
                `${this.connection.gatewayName}-page-information`;
        },

        profileInformationComponentIsLoaded() {
            return Component.getComponentRegistry().has(this.profileInformationComponent);
        },

        credentialsComponent() {
            if (!this.connection || !this.connection.profileName || !this.connection.gatewayName) {
                return '';
            }

            return `swag-migration-profile-${this.connection.profileName}-${this.connection.gatewayName}-credential-form`;
        },
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

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            await this.checkMigrationBackendState();
            await this.initState();

            await this.loadSelectedConnection(this.connectionId);
            this.isLoading = false;
            this.onChildRouteChanged(); // update strings for current child
        },

        async checkMigrationBackendState() {
            try {
                const response = await this.migrationApiService.getState();
                if (!response || !response.step) {
                    return;
                }

                if (response.step !== MIGRATION_STEP.IDLE) {
                    this.$router.push({ name: 'swag.migration.processScreen' });
                }
            } catch {
                // do nothing
            }
        },

        async initState() {
            const forceFullStateReload = this.$route.query.forceFullStateReload ?? false;
            await State.dispatch('swagMigration/init', forceFullStateReload);
            this.storesInitializing = false;
        },

        getRoutes() {
            return {
                introduction: {
                    name: 'swag.migration.wizard.introduction',
                    index: 0,
                    titleSnippet: 'swag-migration.wizard.pages.introduction.title',
                },
                profileInstallation: {
                    name: 'swag.migration.wizard.profileInstallation',
                    index: 0.1,
                    titleSnippet: 'swag-migration.wizard.pages.profileInstallation.title',
                },
                connectionCreate: {
                    name: 'swag.migration.wizard.connectionCreate',
                    index: 0.2, // not available through nextRoute (child from profile)
                    titleSnippet: 'swag-migration.wizard.pages.connectionCreate.title',
                },
                connectionSelect: {
                    name: 'swag.migration.wizard.connectionSelect',
                    index: 0.3, // not available through nextRoute (child from profile)
                    titleSnippet: 'swag-migration.wizard.pages.connectionSelect.title',
                },
                profileInformation: {
                    name: 'swag.migration.wizard.profileInformation',
                    index: 1,
                    titleSnippet: 'swag-migration.wizard.pages.profileInformation.title',
                },
                credentials: {
                    name: 'swag.migration.wizard.credentials',
                    index: 2,
                    titleSnippet: 'swag-migration.wizard.pages.credentials.title',
                },
                credentialsSuccess: {
                    name: 'swag.migration.wizard.credentialsSuccess',
                    index: 2.1, // not available through nextRoute (child from credentials)
                    titleSnippet: 'swag-migration.wizard.pages.credentials.statusTitle',
                },
                credentialsError: {
                    name: 'swag.migration.wizard.credentialsError',
                    index: 2.1, // not available through nextRoute (child from credentials)
                    titleSnippet: 'swag-migration.wizard.pages.credentials.statusTitle',
                },
            };
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
            return this.migrationApiService.updateConnectionCredentials(
                this.connection.id,
                this.connection.credentialFields,
            ).then((response) => {
                if (response.errors && response.errors.length > 0) {
                    this.isLoading = false;
                    this.onResponseError('');
                }

                return this.doConnectionCheck();
            }).catch((error) => {
                this.isLoading = false;
                this.onResponseError(error.response.data.errors[0].code);
            });
        },

        doConnectionCheck() {
            this.isLoading = true;
            return this.migrationApiService.checkConnection(this.connection.id).then((connectionCheckResponse) => {
                State.commit('swagMigration/setConnectionId', this.connection.id);
                this.isLoading = false;

                if (!connectionCheckResponse) {
                    this.onResponseError(-1);
                    return;
                }
                State.commit('swagMigration/setEnvironmentInformation', connectionCheckResponse);
                State.commit('swagMigration/setDataSelectionIds', []);
                State.commit('swagMigration/setPremapping', []);
                State.commit('swagMigration/setDataSelectionTableData', []);

                if (connectionCheckResponse.requestStatus === undefined) {
                    this.navigateToRoute(this.routes.credentialsSuccess);
                    return;
                }

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

                this.navigateToRoute(this.routes.credentialsSuccess);
            }).catch((error) => {
                this.isLoading = false;
                State.commit('swagMigration/setConnectionId', this.connection.id);
                State.commit('swagMigration/setEnvironmentInformation', {});
                State.commit('swagMigration/setDataSelectionIds', []);
                State.commit('swagMigration/setPremapping', []);
                State.commit('swagMigration/setDataSelectionTableData', []);
                this.onResponseError(error.response.data.errors[0].code);
            });
        },

        onResponseError(errorCode) {
            if (errorCode !== '') {
                this.errorMessageSnippet = `swag-migration.wizard.pages.credentials.error.${errorCode}`;

                if (this.$te(`swag-migration.wizard.pages.credentials.error.${errorCode}__HINT`)) {
                    this.errorMessageHintSnippet = `swag-migration.wizard.pages.credentials.error.${errorCode}__HINT`;
                }
            } else {
                this.errorMessageSnippet = '';
                this.errorMessageHintSnippet = '';
            }

            if (this.errorMessageSnippet === this.$tc(`swag-migration.wizard.pages.credentials.error.${errorCode}`)) {
                this.errorMessageSnippet = 'swag-migration.wizard.pages.credentials.error.undefinedErrorMsg';

                if (this.$te('swag-migration.wizard.pages.credentials.error.undefinedErrorMsg__HINT')) {
                    this.errorMessageHintSnippet = 'swag-migration.wizard.pages.credentials.error.undefinedErrorMsg__HINT';
                }
            }

            this.currentErrorCode = errorCode;

            this.navigateToRoute(this.routes.credentialsError);
        },

        onCloseModal() {
            this.showModal = false;
            // navigate to module
            this.$router.push({
                name: 'swag.migration.index.main',
                query: {
                    forceFullStateReload: true,
                },
            });
        },

        onChildRouteChanged() {
            if (this.isLoading) {
                return;
            }

            this.checkForDisabledRoute();
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
                    this.connectionNameErrorCode = CONNECTION_NAME_ERRORS.NAME_ALREADY_EXISTS;
                    this.isLoading = false;
                });
                return;
            }

            if (this.currentRoute === this.routes.connectionSelect) {
                this.saveSelectedConnection(this.connection).then(() => {
                    return this.doConnectionCheck();
                }).catch(() => {
                    this.isLoading = false;
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
                if (this.currentErrorCode === SSL_REQUIRED_ERROR_CODE) {
                    this.connection.credentialFields.endpoint =
                        this.connection.credentialFields.endpoint.replace('http:', 'https:');
                    this.onConnect();
                    return;
                }

                // clicked Edit
                this.navigateToRoute(this.routes.credentials);
                return;
            }

            if (this.currentRoute === this.routes.profileInstallation) {
                this.navigateToRoute(this.routes.connectionCreate);
                return;
            }

            this.navigateToNext();
        },

        async loadSelectedConnection(connectionId) {
            // resolve if connection is already loaded
            if (Object.keys(this.connection).length) {
                return;
            }

            this.isLoading = true;

            if (connectionId !== undefined) {
                await this.fetchConnection(connectionId);
                return;
            }

            const criteria = new Criteria(1, 1);
            const items = this.migrationGeneralSettingRepository.search(criteria, this.context);
            if (items.length < 1) {
                this.isLoading = false;
                this.onNoConnectionSelected();
                return;
            }

            if (items.first().selectedConnectionId === null) {
                this.isLoading = false;
                this.onNoConnectionSelected();
                return;
            }

            await this.fetchConnection(items.first().selectedConnectionId);
        },

        fetchConnection(connectionId) {
            return new Promise((resolve) => {
                const criteria = new Criteria(1, 1);
                criteria.addFilter(Criteria.equals('id', connectionId));

                this.migrationConnectionRepository.search(criteria, this.context).then((connectionResponse) => {
                    if (connectionResponse.length === 0 || connectionResponse.first().id === null) {
                        this.isLoading = false;
                        this.onNoConnectionSelected();
                        resolve();
                        return;
                    }

                    this.connection = connectionResponse.first();
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
                this.routes.credentialsError,
            ].includes(this.currentRoute)) {
                this.navigateToRoute(this.routes.profileInstallation);
            }
        },

        createNewConnection() {
            this.isLoading = true;
            return this.checkConnectionName(this.connectionName).then((valid) => {
                if (!valid) {
                    this.isLoading = false;
                    return Promise.reject();
                }

                this.connectionNameErrorCode = '';
                const newConnection = this.migrationConnectionRepository.create(this.context);
                newConnection.profileName = this.selectedProfile.profile;
                newConnection.gatewayName = this.selectedProfile.gateway;
                newConnection.name = this.connectionName;
                return this.migrationConnectionRepository.save(newConnection, this.context).then(() => {
                    return this.saveSelectedConnection(newConnection);
                });
            });
        },

        checkConnectionName(name) {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('name', name));

            return this.migrationConnectionRepository.search(criteria, this.context).then((res) => {
                return res.length === 0;
            });
        },

        saveSelectedConnection(connection) {
            return new Promise((resolve, reject) => {
                this.isLoading = true;

                State.commit('swagMigration/setConnectionId', connection.id);
                State.commit('swagMigration/setEnvironmentInformation', {});
                State.commit('swagMigration/setDataSelectionIds', []);
                State.commit('swagMigration/setPremapping', []);
                State.commit('swagMigration/setDataSelectionTableData', []);

                const criteria = new Criteria(1, 1);

                this.migrationGeneralSettingRepository.search(criteria, this.context).then((items) => {
                    if (items.length < 1) {
                        this.isLoading = false;
                        reject();
                        return;
                    }

                    const setting = items.first();
                    setting.selectedConnectionId = connection.id;
                    this.migrationGeneralSettingRepository.save(setting, this.context).then(() => {
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
            if (this.connectionName !== null && this.connectionName.length > 0) {
                this.connectionNameErrorCode = '';
                return;
            }

            this.connectionNameErrorCode = CONNECTION_NAME_ERRORS.NAME_TO_SHORT;
        },

        onChildIsLoadingChanged(value) {
            this.childIsLoading = value;
        },

        onConnectionSelected(value) {
            this.connection = value;
        },
    },
});
