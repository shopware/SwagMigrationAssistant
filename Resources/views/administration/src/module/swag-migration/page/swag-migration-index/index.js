import { Component, State } from 'src/core/shopware';
import template from './swag-migration-index.html.twig';
import { MIGRATION_STATUS } from '../../../../core/service/migration/swag-migration-worker-status-manager.service';
import { UI_COMPONENT_INDEX } from '../../../../core/data/MigrationUIStore';

Component.register('swag-migration-index', {
    template,

    data() {
        return {
            /** @type MigrationUIStore */
            migrationUIStore: State.getStore('migrationUI'),
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess')
        };
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

        /**
         * @returns {boolean}
         */
        migrateButtonDisabled() {
            return this.migrationUIStore.state.isLoading ||
                (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.FETCH_DATA &&
                    this.migrationProcessStore.state.isMigrating) ||
                !this.migrationUIStore.state.isMigrationAllowed ||
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
