import { Component, State } from 'src/core/shopware';
import template from './swag-migration-index.html.twig';

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
            /** @type MigrationUIStore */
            migrationUIStore: State.getStore('migrationUI'),
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess'),
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
            return this.migrationUIStore.getIsMigrationAllowed();
        },

        /**
         * @returns {boolean}
         */
        migrateButtonDisabled() {
            return this.migrationUIStore.state.isLoading ||
                this.migrationProcessStore.state.isMigrating ||
                !this.isMigrationAllowed;
        }
    },

    methods: {
        createdComponent() {
            if (this.migrationProcessStore.state.connectionId === null
                || Object.keys(this.migrationProcessStore.state.environmentInformation).length === 0
            ) {
                this.migrationProcessStoreInitService.initProcessStore().then(() => {
                    return this.migrationUiStoreInitService.initUiStore();
                }).catch(() => {
                    this.$router.push({ name: 'swag.migration.emptyScreen' });
                }).finally(() => {
                    this.storesInitializing = false;
                });
            } else if (this.migrationUIStore.state.dataSelectionTableData.length === 0) {
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
