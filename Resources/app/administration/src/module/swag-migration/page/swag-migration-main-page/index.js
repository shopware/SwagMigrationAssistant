import template from './swag-migration-main-page.html.twig';
import './swag-migration-main-page.scss';

const { Component } = Shopware;

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
            migrationUIState: this.$store.state['swagMigration/ui'],
            migrationProcessState: this.$store.state['swagMigration/process']
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        displayWarnings() {
            return this.migrationProcessState.environmentInformation.displayWarnings;
        },

        connectionEstablished() {
            return this.migrationProcessState.environmentInformation !== undefined &&
                (
                    this.migrationProcessState.environmentInformation.requestStatus.isWarning === true ||
                    (
                        this.migrationProcessState.environmentInformation.requestStatus.isWarning === false &&
                        this.migrationProcessState.environmentInformation.requestStatus.code === ''
                    )
                );
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.$store.commit('swagMigration/ui/setIsLoading', true);

            if (this.migrationProcessState.connectionId === null) {
                this.$router.push({ name: 'swag.migration.wizard.introduction' });
                return;
            }

            if (Object.keys(this.migrationProcessState.environmentInformation).length < 1) {
                this.$router.push({ name: 'swag.migration.emptyScreen' });
                return;
            }

            if (this.migrationProcessState.isMigrating) {
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

            this.$store.commit('swagMigration/ui/setIsLoading', false);
        },

        async onMigrate() {
            this.$nextTick().then(() => {
                this.$store.commit('swagMigration/process/setIsMigrating', true);
                /**
                 * reset the premapping because it does not get fetched again if not empty
                 * this will ensure that the user can navigate outside of the module and keep the premapping
                 */
                this.$store.commit('swagMigration/ui/setPremapping', []);

                // navigate to process screen
                this.$store.commit('swagMigration/ui/setIsLoading', true);
                this.$router.push({ name: 'swag.migration.processScreen', params: { startMigration: true } });
            });
        }
    }
});
