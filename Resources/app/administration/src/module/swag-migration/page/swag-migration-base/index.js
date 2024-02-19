import template from './swag-migration-base.html.twig';

const { Component, State } = Shopware;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-base', {
    template,

    inject: {
        /** @var {MigrationProcessStoreInitService} migrationProcessStoreInitService */
        migrationProcessStoreInitService: 'processStoreInitService',
        /** @var {MigrationUiStoreInitService} migrationUiStoreInitService */
        migrationUiStoreInitService: 'uiStoreInitService',
    },

    data() {
        return {
            storesInitializing: true,
        };
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
        ]),

        ...mapState('swagMigration/ui', [
            'isLoading',
            'dataSelectionTableData',
        ]),

        ...mapGetters({
            storeIsMigrationAllowed: 'swagMigration/ui/isMigrationAllowed',
        }),

        isMigrationAllowed() {
            return this.storeIsMigrationAllowed &&
                this.environmentInformation.migrationDisabled === false;
        },

        /**
         * @returns {boolean}
         */
        migrateButtonDisabled() {
            return this.isLoading ||
                !this.isMigrationAllowed;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            return this.migrationProcessStoreInitService.initProcessStore()
                .then(() => {
                    return this.migrationUiStoreInitService.initUiStore();
                })
                .then(() => {
                    const isMigrationRunning = false;
                    // ToDo MIG-895: implement check if migration is running
                    if (isMigrationRunning) {
                        this.$router.push({ name: 'swag.migration.processScreen' });
                    }
                })
                .catch(() => {
                    // ToDo MIG-895: handle error
                })
                .finally(() => {
                    this.storesInitializing = false;
                });
        },

        onMigrate() {
            this.$nextTick(() => {
                // navigate to process screen
                State.commit('swagMigration/ui/setIsLoading', true);
                this.$router.push({ name: 'swag.migration.processScreen' });
            });
        },
    },
});
