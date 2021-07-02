import template from './swag-migration-main-page.html.twig';
import './swag-migration-main-page.scss';

const { Component, State } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-migration-main-page', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService',
        /** @var {MigrationWorkerService} migrationWorkerService */
        migrationWorkerService: 'migrationWorkerService',
        /** @var {MigrationProcessStoreInitService} migrationProcessStoreInitService */
        migrationProcessStoreInitService: 'processStoreInitService',
        /** @var {MigrationUiStoreInitService} migrationUiStoreInitService */
        migrationUiStoreInitService: 'uiStoreInitService',
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        ...mapState('swagMigration/process', [
            'environmentInformation',
            'connectionId',
            'isMigrating',
        ]),

        ...mapState('swagMigration/ui', [
            'isLoading',
        ]),

        displayWarnings() {
            return this.environmentInformation.displayWarnings;
        },

        connectionEstablished() {
            return this.environmentInformation !== undefined &&
                (
                    this.environmentInformation.requestStatus.isWarning === true ||
                    (
                        this.environmentInformation.requestStatus.isWarning === false &&
                        this.environmentInformation.requestStatus.code === ''
                    )
                );
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            State.commit('swagMigration/ui/setIsLoading', true);

            if (this.connectionId === null) {
                this.$router.push({ name: 'swag.migration.wizard.introduction' });
                return;
            }

            if (Object.keys(this.environmentInformation).length < 1) {
                this.$router.push({ name: 'swag.migration.emptyScreen' });
                return;
            }

            if (this.isMigrating) {
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

            State.commit('swagMigration/ui/setIsLoading', false);
        },

        async onMigrate() {
            this.$nextTick().then(() => {
                State.commit('swagMigration/process/setIsMigrating', true);
                /**
                 * reset the premapping because it does not get fetched again if not empty
                 * this will ensure that the user can navigate outside of the module and keep the premapping
                 */
                State.commit('swagMigration/ui/setPremapping', []);

                // navigate to process screen
                State.commit('swagMigration/ui/setIsLoading', true);
                this.$router.push({ name: 'swag.migration.processScreen', params: { startMigration: true } });
            });
        },
    },
});
