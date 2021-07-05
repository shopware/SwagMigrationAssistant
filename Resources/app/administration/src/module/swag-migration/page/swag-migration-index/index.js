import template from './swag-migration-index.html.twig';

const { Component } = Shopware;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('swag-migration-index', {
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
            'isMigrating',
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
                this.isMigrating ||
                !this.isMigrationAllowed;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.connectionId === null
                || Object.keys(this.environmentInformation).length === 0
            ) {
                return this.migrationProcessStoreInitService.initProcessStore().then(() => {
                    return this.migrationUiStoreInitService.initUiStore();
                }).catch(() => {}).finally(() => {
                    this.storesInitializing = false;
                });
            }

            if (this.dataSelectionTableData.length === 0) {
                return this.migrationUiStoreInitService.initUiStore().then(() => {
                    this.storesInitializing = false;
                }).catch(() => {
                    this.storesInitializing = false;
                });
            }

            this.storesInitializing = false;
            return Promise.resolve();
        },

        onMigrate() {
            if (this.$refs.contentComponent.onMigrate !== undefined) {
                this.$refs.contentComponent.onMigrate();
            } else {
                this.$nextTick(() => {
                    this.$router.push({ name: 'swag.migration.index.main', params: { startMigration: true } });
                });
            }
        },

        /**
         * Calls methods on the child router view component (contentComponent) dynamically
         * if existing. This is used to trigger some method on the child via action button.
         *
         * @param methodName
         */
        onActionButtonClick(methodName) {
            if (this.$refs.contentComponent[methodName] !== undefined) {
                this.$refs.contentComponent[methodName]();
            }
        },
    },
});
