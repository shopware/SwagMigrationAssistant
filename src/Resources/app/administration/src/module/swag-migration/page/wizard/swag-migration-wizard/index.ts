import template from './swag-migration-wizard.html.twig';
import './swag-migration-wizard.scss';
import { MIGRATION_API_SERVICE, MIGRATION_STEP } from '../../../../../core/service/api/swag-migration.api.service';
import type { MigrationConnection, MigrationCredentials, MigrationProfile, TRepository } from '../../../../../type/types';
import type { MigrationStore } from '../../../store/migration.store';
import { MIGRATION_STORE_ID } from '../../../store/migration.store';

const { Mixin, Store } = Shopware;
const { Criteria } = Shopware.Data;
const { mapState } = Shopware.Component.getComponentHelper();

const SSL_REQUIRED_ERROR_CODE = 'SWAG_MIGRATION__SSL_REQUIRED';

const CONNECTION_NAME_ERRORS = {
    NAME_TO_SHORT: 'SWAG_MIGRATION_CONNECTION_NAME_TO_SHORT',
    NAME_ALREADY_EXISTS: 'SWAG_MIGRATION_CONNECTION_NAME_ALREADY_EXISTS',
} as const;

/**
 * @private
 */
export const ROUTES = {
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
} as const;

/**
 * @private
 */
export interface SwagMigrationWizardData {
    context: unknown;
    storesInitializing: boolean;
    showModal: boolean;
    isLoading: boolean;
    childIsLoading: boolean;
    routes: typeof ROUTES;
    connection: MigrationConnection;
    connectionName: string;
    selectedProfile: MigrationProfile;
    childRouteReady: boolean; // child routes with forms will emit and change this value depending on their validation.
    errorMessageSnippet: string;
    errorMessageHintSnippet: string;
    connectionNameErrorCode: string;
    currentErrorCode: string;
    migrationStore: MigrationStore;
    isNewConnection: boolean;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 *
 * Note:
 * This component should not inherit from another component, because Rufus is overriding it and NEXT-36774 breaks it then.
 * We might inherit from 'swag-migration-base' in the future again.
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
        'repositoryFactory',
    ],

    mixins: [
        Mixin.getByName('swag-wizard'),
    ],

    data(): SwagMigrationWizardData {
        return {
            context: Shopware.Context.api,
            storesInitializing: true,
            showModal: true,
            isLoading: true,
            childIsLoading: false,
            routes: ROUTES,
            connection: {} as MigrationConnection,
            connectionName: '',
            selectedProfile: {} as MigrationProfile,
            childRouteReady: false, // child routes with forms will emit and change this value depending on their validation.
            errorMessageSnippet: '',
            errorMessageHintSnippet: '',
            connectionNameErrorCode: '',
            currentErrorCode: '',
            migrationStore: Store.get(MIGRATION_STORE_ID),
            isNewConnection: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        ...mapState(
            () => Store.get(MIGRATION_STORE_ID),
            [
                'connectionId',
            ],
        ),

        migrationConnectionRepository(): TRepository<'swag_migration_connection'> {
            return this.repositoryFactory.create('swag_migration_connection');
        },

        migrationGeneralSettingRepository(): TRepository<'swag_migration_general_setting'> {
            return this.repositoryFactory.create('swag_migration_general_setting');
        },

        modalSize() {
            if (
                [
                    this.routes.credentialsSuccess,
                    this.routes.credentialsError,
                ].includes(this.currentRoute)
            ) {
                return '460px';
            }

            return '688px';
        },

        modalTitleSnippet() {
            return this.currentRoute.titleSnippet;
        },

        buttonSecondarySnippet() {
            if (this.currentRoute === this.routes.credentialsError) {
                return 'swag-migration.wizard.buttonLater';
            }

            return 'swag-migration.wizard.buttonAbort';
        },

        buttonSecondaryVisible() {
            return this.currentRoute !== this.routes.credentialsSuccess;
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
            if (
                [
                    this.routes.credentials,
                    this.routes.connectionCreate,
                    this.routes.connectionSelect,
                ].includes(this.currentRoute)
            ) {
                return !this.childRouteReady || this.isLoading;
            }

            return this.isLoading;
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
    beforeRouteLeave(to: never, from: never, next: () => void) {
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
            const response = await this.migrationApiService.getState().catch();

            if (!response || !response.step) {
                return;
            }

            if (response.step !== MIGRATION_STEP.IDLE) {
                await this.$router.push({ name: 'swag.migration.processScreen' });
            }
        },

        async initState() {
            const forceFullStateReload = this.$route.query.forceFullStateReload ?? false;

            await Store.get(MIGRATION_STORE_ID).init(
                this.migrationApiService,
                this.migrationGeneralSettingRepository,
                forceFullStateReload,
            );

            this.storesInitializing = false;
        },

        /**
         * Remove any whitespaces before or after the strings in the credentials object.
         */
        trimCredentials() {
            Object.keys(this.connection.credentialFields).forEach((field) => {
                this.connection.credentialFields[field] = this.connection.credentialFields[field].trim();
            });
        },

        async onConnect() {
            this.isLoading = true;
            this.errorMessageSnippet = '';

            this.trimCredentials();

            try {
                if (this.isNewConnection) {
                    const environmentInformation = await this.migrationApiService.createNewConnection(
                        this.connection.id,
                        this.connectionName,
                        this.selectedProfile.profile,
                        this.selectedProfile.gateway,
                        this.connection.credentialFields,
                    );
                    this.isNewConnection = false;

                    await this.saveSelectedConnection(this.connection);
                    this.migrationStore.setEnvironmentInformation(environmentInformation);

                    this.navigateToRoute(this.routes.credentialsSuccess);
                } else {
                    const isValid = await this.doConnectionCheck(this.connection.credentialFields);

                    if (isValid) {
                        await this.migrationApiService.updateConnectionCredentials(
                            this.connection.id,
                            this.connection.credentialFields,
                        );
                    }
                }
            } catch (error) {
                this.onResponseError(error.response.data.errors[0].code);
            } finally {
                this.isLoading = false;
            }
        },

        doConnectionCheck(credentialFields?: Record<string, string>) {
            this.isLoading = true;

            return this.migrationApiService
                .checkConnection(this.connection.id, credentialFields)
                .then((connectionCheckResponse) => {
                    this.migrationStore.setConnectionId(this.connection.id);
                    this.isLoading = false;

                    if (!connectionCheckResponse) {
                        this.onResponseError(-1);
                        return false;
                    }

                    this.migrationStore.setEnvironmentInformation(connectionCheckResponse);
                    this.migrationStore.setDataSelectionIds([]);
                    this.migrationStore.setPremapping([]);
                    this.migrationStore.setDataSelectionTableData([]);

                    if (connectionCheckResponse.requestStatus === undefined) {
                        this.navigateToRoute(this.routes.credentialsSuccess);
                        return true;
                    }

                    if (
                        connectionCheckResponse.requestStatus.code !== '' &&
                        connectionCheckResponse.requestStatus.isWarning === false
                    ) {
                        this.onResponseError(connectionCheckResponse.requestStatus.code);
                        return false;
                    }

                    // create warning for success page
                    this.errorMessageSnippet = '';
                    if (
                        connectionCheckResponse.requestStatus.code !== '' &&
                        connectionCheckResponse.requestStatus.isWarning === true
                    ) {
                        // eslint-disable-next-line max-len
                        this.errorMessageSnippet = `swag-migration.wizard.pages.credentials.success.${connectionCheckResponse.requestStatus.code}`;
                    }

                    this.navigateToRoute(this.routes.credentialsSuccess);

                    return true;
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.migrationStore.setConnectionId(this.connection.id);
                    this.migrationStore.setEnvironmentInformation({});
                    this.migrationStore.setDataSelectionIds([]);
                    this.migrationStore.setPremapping([]);
                    this.migrationStore.setDataSelectionTableData([]);
                    this.onResponseError(error.response.data.errors[0].code);

                    return false;
                });
        },

        onResponseError(errorCode: string) {
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
            }
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
                this.createNewConnection()
                    .then(() => {
                        this.navigateToNext();
                    })
                    .catch(() => {
                        this.connectionNameErrorCode = CONNECTION_NAME_ERRORS.NAME_ALREADY_EXISTS;
                        this.isLoading = false;
                    });
                return;
            }

            if (this.currentRoute === this.routes.connectionSelect) {
                this.saveSelectedConnection(this.connection)
                    .then(() => {
                        return this.doConnectionCheck();
                    })
                    .catch(() => {
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
                    this.connection.credentialFields.endpoint = this.connection.credentialFields.endpoint.replace(
                        'http:',
                        'https:',
                    );
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

        async loadSelectedConnection(connectionId: string) {
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

        fetchConnection(connectionId: string) {
            return new Promise((resolve) => {
                const criteria = new Criteria(1, 1);
                criteria.addFilter(Criteria.equals('id', connectionId));

                this.migrationConnectionRepository.search(criteria, this.context).then((connectionResponse) => {
                    if (connectionResponse.length === 0 || connectionResponse.first().id === null) {
                        this.isLoading = false;
                        this.onNoConnectionSelected();
                        resolve(null);
                        return;
                    }

                    this.connection = connectionResponse.first();
                    this.isLoading = false;
                    resolve(null);
                });
            });
        },

        onNoConnectionSelected() {
            if (
                [
                    this.routes.chooseAction,
                    this.routes.credentials,
                    this.routes.credentialsSuccess,
                    this.routes.credentialsError,
                ].includes(this.currentRoute)
            ) {
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
                this.connection = this.migrationConnectionRepository.create(this.context);
                this.connection.profileName = this.selectedProfile.profile;
                this.connection.gatewayName = this.selectedProfile.gateway;
                this.connection.name = this.connectionName;

                this.isNewConnection = true;
                this.isLoading = false;

                return Promise.resolve();
            });
        },

        async checkConnectionName(name: string): Promise<boolean> {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('name', name));

            return this.migrationConnectionRepository.search(criteria, this.context).then((res) => {
                return res.length === 0;
            });
        },

        saveSelectedConnection(connection: MigrationConnection) {
            return new Promise((resolve, reject) => {
                this.isLoading = true;

                this.migrationStore.setConnectionId(connection.id);
                this.migrationStore.setEnvironmentInformation({});
                this.migrationStore.setDataSelectionIds([]);
                this.migrationStore.setPremapping([]);
                this.migrationStore.setDataSelectionTableData([]);

                const criteria = new Criteria(1, 1);

                this.migrationGeneralSettingRepository
                    .search(criteria, this.context)
                    .then((items) => {
                        if (items.length < 1) {
                            this.isLoading = false;
                            reject();
                            return;
                        }

                        const setting = items.first();
                        setting.selectedConnectionId = connection.id;
                        this.migrationGeneralSettingRepository
                            .save(setting, this.context)
                            .then(() => {
                                this.connection = connection;
                                this.isLoading = false;
                                resolve(null);
                            })
                            .catch(() => {
                                this.isLoading = false;
                                reject();
                            });
                    })
                    .catch(() => {
                        this.isLoading = false;
                        reject();
                    });
            });
        },

        onChildRouteReadyChanged(value: boolean) {
            this.childRouteReady = value;
        },

        onCredentialsChanged(value: MigrationCredentials) {
            this.connection.credentialFields = value;
        },

        onProfileSelected(value: MigrationProfile) {
            this.selectedProfile = value;
        },

        onChangeConnectionName(value: string) {
            this.connectionName = value;

            if (this.connectionName !== null && this.connectionName.length > 0) {
                this.connectionNameErrorCode = '';
                return;
            }

            this.connectionNameErrorCode = CONNECTION_NAME_ERRORS.NAME_TO_SHORT;
        },

        onChildIsLoadingChanged(value: boolean) {
            this.childIsLoading = value;
        },

        onConnectionSelected(value: MigrationConnection | null) {
            this.connection = value;
        },
    },
});
