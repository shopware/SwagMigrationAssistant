import { Component, State } from 'src/core/shopware';
import template from './swag-migration-main-page.html.twig';
import './swag-migration-main-page.scss';

Component.register('swag-migration-main-page', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService',
        /** @var {MigrationWorkerService} migrationWorkerService */
        migrationWorkerService: 'migrationWorkerService',
        /** @var {ApiService} swagMigrationRunService */
        swagMigrationRunService: 'swagMigrationRunService',
        /** @var {MigrationProcessStoreInitService} migrationProcessStoreInitService */
        migrationProcessStoreInitService: 'processStoreInitService',
        /** @var {MigrationUiStoreInitService} migrationUiStoreInitService */
        migrationUiStoreInitService: 'uiStoreInitService'
    },

    data() {
        return {
            connectionEstablished: false,
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess'),
            /** @type MigrationUIStore */
            migrationUIStore: State.getStore('migrationUI')
        };
    },

    computed: {
        isUpdateAvailable() {
            return (
                this.migrationProcessStore.state.environmentInformation.updateAvailable !== null
                && this.migrationProcessStore.state.environmentInformation.updateAvailable === true
            );
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.migrationUIStore.setIsLoading(true);

            if (this.migrationProcessStore.state.connectionId === null) {
                this.$router.push({ name: 'swag.migration.wizard.introduction' });
                return;
            }

            if (this.migrationProcessStore.state.isMigrating) {
                this.$router.push({ name: 'swag.migration.processScreen' });
                return;
            }

            let isMigrationRunning = false;
            await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                isMigrationRunning = runState.isMigrationRunning;
            });

            if (isMigrationRunning) {
                this.$router.push({ name: 'swag.migration.processScreen' });
                return;
            }

            // Do connection check
            this.migrationService.checkConnection(this.migrationProcessStore.state.connectionId)
                .then(async (connectionCheckResponse) => {
                    this.migrationProcessStore.setEnvironmentInformation(connectionCheckResponse);
                    this.connectionEstablished = (connectionCheckResponse.errorCode === '');

                    if (this.$route.params.startMigration) {
                        await this.onMigrate();
                    }

                    this.migrationUIStore.setIsLoading(false);
                }).catch(() => {
                    this.connectionEstablished = false;
                    this.migrationUIStore.setIsLoading(false);
                });
        },

        async onMigrate() {
            this.$nextTick().then(() => {
                this.migrationProcessStore.setIsMigrating(true);
                /**
                 * reset the premapping because it does not get fetched again if not empty
                 * this will ensure that the user can navigate outside of the module and keep the premapping
                 */
                this.migrationUIStore.setPremapping([]);

                // navigate to process screen
                this.migrationUIStore.setIsLoading(true);
                this.$router.push({ name: 'swag.migration.processScreen', params: { startMigration: true } });
            });
        }
    }
});
