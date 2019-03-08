import { Component, State } from 'src/core/shopware';
import template from './swag-migration-index.html.twig';
import { MIGRATION_STATUS } from '../../../../core/service/migration/swag-migration-worker-status-manager.service';
import { UI_COMPONENT_INDEX } from '../../../../core/data/MigrationUIStore';

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
            storesInitializing: true,
            showMigrationConfirmDialog: false
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        /**
         * @returns {boolean}
         */
        componentIndexIsResult() {
            return (this.migrationUIStore.state.componentIndex === UI_COMPONENT_INDEX.RESULT_SUCCESS ||
                this.migrationUIStore.state.componentIndex === UI_COMPONENT_INDEX.RESULT_WARNING ||
                this.migrationUIStore.state.componentIndex === UI_COMPONENT_INDEX.RESULT_FAILURE);
        },

        /**
         * @returns {boolean}
         */
        abortButtonVisible() {
            return this.migrationUIStore.state.isPaused || (
                this.migrationProcessStore.state.isMigrating &&
                !this.migrationUIStore.state.isLoading &&
                !this.componentIndexIsResult
            );
        },

        /**
         * @returns {boolean}
         */
        backButtonVisible() {
            return this.componentIndexIsResult;
        },

        /**
         * @returns {boolean}
         */
        migrateButtonVisible() {
            return (!this.migrationProcessStore.state.isMigrating && !this.migrationUIStore.state.isPaused) ||
                (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.FETCH_DATA &&
                    this.migrationProcessStore.state.isMigrating) ||
                (this.componentIndexIsResult && this.migrationProcessStore.state.isMigrating);
        },

        isMigrationAllowed() {
            return this.migrationUIStore.getIsMigrationAllowed();
        },

        /**
         * @returns {boolean}
         */
        migrateButtonDisabled() {
            return this.migrationUIStore.state.isLoading ||
                (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.FETCH_DATA &&
                    this.migrationProcessStore.state.isMigrating) ||
                !this.isMigrationAllowed ||
                this.componentIndexIsResult;
        },

        /**
         * @returns {boolean}
         */
        startButtonVisible() {
            return (!this.migrationUIStore.state.isLoading &&
                this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.PREMAPPING &&
                this.migrationProcessStore.state.isMigrating);
        },

        /**
         * @returns {boolean}
         */
        startButtonDisabled() {
            return this.migrationUIStore.state.isLoading ||
                (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.PREMAPPING &&
                    this.migrationProcessStore.state.isMigrating && !this.migrationUIStore.state.isPremappingValid);
        },

        /**
         * @returns {boolean}
         */
        pauseButtonVisible() {
            return this.migrationProcessStore.state.isMigrating &&
                !this.migrationUIStore.state.isPaused &&
                this.migrationProcessStore.state.statusIndex !== MIGRATION_STATUS.WAITING &&
                this.migrationProcessStore.state.statusIndex !== MIGRATION_STATUS.FETCH_DATA &&
                this.migrationProcessStore.state.statusIndex !== MIGRATION_STATUS.PREMAPPING &&
                !this.componentIndexIsResult;
        },

        /**
         * @returns {boolean}
         */
        pauseButtonDisabled() {
            return this.migrationUIStore.state.isLoading;
        },

        /**
         * @returns {boolean}
         */
        continueButtonVisible() {
            return this.migrationUIStore.state.isPaused;
        }
    },

    methods: {
        createdComponent() {
            if (this.migrationProcessStore.state.connectionId === null
                || this.migrationProcessStore.state.environmentInformation === null
            ) {
                this.migrationProcessStoreInitService.initProcessStore().then(() => {
                    return this.migrationUiStoreInitService.initUiStore();
                }).then(() => {
                    this.storesInitializing = false;
                }).catch(() => {
                    this.storesInitializing = false;
                });
            } else if (this.migrationUIStore.state.dataSelectionTableData.length === 0) {
                this.migrationUiStoreInitService.initUiStore().then(() => {
                    this.storesInitializing = false;
                });
            } else {
                this.storesInitializing = false;
            }
        },

        onMigrateButtonClick() {
            this.showMigrationConfirmDialog = true;
        },

        onCloseMigrationConfirmDialog() {
            this.showMigrationConfirmDialog = false;
        },

        onMigrate() {
            this.showMigrationConfirmDialog = false;

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
