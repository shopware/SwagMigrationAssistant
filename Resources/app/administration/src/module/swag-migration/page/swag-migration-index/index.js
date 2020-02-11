import template from './swag-migration-index.html.twig';

const { Component } = Shopware;

Component.register('swag-migration-index', {
    template,

    inject: {
        /** @var {MigrationProcessStoreInitService} migrationProcessStoreInitService */
        migrationProcessStoreInitService: 'processStoreInitService',
        /** @var {MigrationUiStoreInitService} migrationUiStoreInitService */
        migrationUiStoreInitService: 'uiStoreInitService'
    },

    data() {
        return {
            migrationUIState: this.$store.state['swagMigration/ui'],
            migrationProcessState: this.$store.state['swagMigration/process'],
            storesInitializing: true
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        isMigrationAllowed() {
            return this.$store.getters['swagMigration/ui/isMigrationAllowed'] &&
                this.migrationProcessState.environmentInformation.migrationDisabled === false;
        },

        /**
         * @returns {boolean}
         */
        migrateButtonDisabled() {
            return this.migrationUIState.isLoading ||
                this.migrationProcessState.isMigrating ||
                !this.isMigrationAllowed;
        }
    },

    methods: {
        createdComponent() {
            if (this.migrationProcessState.connectionId === null
                || Object.keys(this.migrationProcessState.environmentInformation).length === 0
            ) {
                this.migrationProcessStoreInitService.initProcessStore().then(() => {
                    return this.migrationUiStoreInitService.initUiStore();
                }).catch(() => {}).finally(() => {
                    this.storesInitializing = false;
                });
            } else if (this.migrationUIState.dataSelectionTableData.length === 0) {
                this.migrationUiStoreInitService.initUiStore().then(() => {
                    this.storesInitializing = false;
                }).catch(() => {
                    this.storesInitializing = false;
                });
            } else {
                this.storesInitializing = false;
            }
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
        }
    }
});
