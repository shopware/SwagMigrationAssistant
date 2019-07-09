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
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess'),
            /** @type MigrationUIStore */
            migrationUIStore: State.getStore('migrationUI')
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        displayWarnings() {
            return this.migrationProcessStore.state.environmentInformation.displayWarnings;
        },

        connectionEstablished() {
            return this.migrationProcessStore.state.environmentInformation !== undefined &&
                (
                    this.migrationProcessStore.state.environmentInformation.requestStatus.isWarning === true ||
                    (
                        this.migrationProcessStore.state.environmentInformation.requestStatus.isWarning === false &&
                        this.migrationProcessStore.state.environmentInformation.requestStatus.code === ''
                    )
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

            if (Object.keys(this.migrationProcessStore.state.environmentInformation).length < 1) {
                this.$router.push({ name: 'swag.migration.emptyScreen' });
                return;
            }

            if (this.migrationProcessStore.state.isMigrating) {
                this.$router.push({ name: 'swag.migration.processScreen' });
                return;
            }

            let isTakeoverForbidden = false;
            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                isTakeoverForbidden = isRunning;
            });

            let isMigrationRunning = isTakeoverForbidden;
            if (!isTakeoverForbidden) {
                await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                    isMigrationRunning = runState.isMigrationRunning;
                });
            }

            if (isMigrationRunning) {
                this.$router.push({ name: 'swag.migration.processScreen' });
                return;
            }

            if (this.$route.params.startMigration) {
                await this.onMigrate();
            }

            this.migrationUIStore.setIsLoading(false);
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
