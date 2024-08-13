import template from './swag-migration-process-screen.html.twig';
import './swag-migration-process-screen.scss';
import { MIGRATION_STEP } from '../../../../core/service/api/swag-migration.api.service';

const { Component, State } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

const MIGRATION_STATE_POLLING_INTERVAL = 1000; // ms


const MIGRATION_STEP_DISPLAY_INDEX = Object.freeze({
    [MIGRATION_STEP.IDLE]: 0,
    [MIGRATION_STEP.FETCHING]: 0,
    [MIGRATION_STEP.WRITING]: 1,
    [MIGRATION_STEP.MEDIA_PROCESSING]: 2,
    [MIGRATION_STEP.ABORTING]: 3,
    [MIGRATION_STEP.CLEANUP]: 3,
    [MIGRATION_STEP.INDEXING]: 4,
    [MIGRATION_STEP.WAITING_FOR_APPROVE]: 5,
});

const UI_COMPONENT_INDEX = Object.freeze({
    LOADING_SCREEN: 0,
    RESULT_SUCCESS: 1,
});

/**
 * @private
 * @package services-settings
 */
Component.extend('swag-migration-process-screen', 'swag-migration-base', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationApiService */
        migrationApiService: 'migrationApiService',
    },

    mixins: [
        'notification',
    ],

    metaInfo() {
        return {
            title: this.progressPercentage !== null ?
                `${this.progressPercentage}% ${this.$createTitle()}` :
                this.$createTitle(),
        };
    },

    data() {
        return {
            displayFlowChart: true,
            flowChartItemIndex: 0,
            flowChartItemVariant: 'info',
            flowChartInitialItemVariants: [],
            UI_COMPONENT_INDEX: UI_COMPONENT_INDEX, // accessible to the template
            componentIndex: UI_COMPONENT_INDEX.LOADING_SCREEN,
            showAbortMigrationConfirmDialog: false,
            pollingIntervalId: null,
            step: MIGRATION_STEP.FETCHING,
            progress: 0,
            total: 0,
        };
    },

    computed: {
        ...mapState('swagMigration', [
            'isLoading',
            'dataSelectionIds',
        ]),

        /**
         * @returns {boolean}
         */
        abortButtonVisible() {
            return !this.isLoading &&
                !this.componentIndexIsResult;
        },

        /**
         * @returns {boolean}
         */
        backToOverviewButtonVisible() {
            return !this.isLoading &&
                this.componentIndex === UI_COMPONENT_INDEX.RESULT_SUCCESS;
        },

        /**
         * @returns {boolean}
         */
        backToOverviewButtonDisabled() {
            return this.isLoading;
        },

        abortButtonDisabled() {
            return this.isLoading || this.step === MIGRATION_STEP.ABORTING;
        },

        /**
         * @returns {boolean}
         */
        componentIndexIsResult() {
            return this.componentIndex === UI_COMPONENT_INDEX.RESULT_SUCCESS;
        },

        progressPercentage() {
            if (!this.total) {
                return null;
            }

            // Prevent progress bar and window title from exceeding 100%
            return Math.min(
                Math.round((this.progress / this.total) * 100),
                100,
            );
        },
    },

    unmounted() {
        this.unmountedComponent();
    },

    methods: {
        async createdComponent() {
            await this.initState();
            State.commit('swagMigration/setIsLoading', true);

            if (this.connectionId === null) {
                this.$router.push({ name: 'swag.migration.index.main' });
                return;
            }

            let migrationRunning = false;
            try {
                const state = await this.migrationApiService.getState();
                if (state?.step !== MIGRATION_STEP.IDLE) {
                    migrationRunning = true;
                    this.visualizeMigrationState(state);
                }
            } catch (e) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.getState'),
                });
            }

            if (!migrationRunning) {
                await this.startMigration();
                // update to the new state immediately
                try {
                    const state = await this.migrationApiService.getState();
                    this.visualizeMigrationState(state);
                } catch (e) {
                    this.createNotificationError({
                        title: this.$tc('global.default.error'),
                        message: this.$tc('swag-migration.api-error.getState'),
                    });
                }
            }

            this.registerPolling();
            State.commit('swagMigration/setIsLoading', false);
        },

        async unmountedComponent() {
            this.unregisterPolling();
            this.total = 0; // hide percentage in browser tab title again
        },

        unregisterPolling() {
            if (this.pollingIntervalId) {
                clearInterval(this.pollingIntervalId);
            }
        },

        registerPolling() {
            this.unregisterPolling();
            this.pollingIntervalId = setInterval(this.migrationStatePoller, MIGRATION_STATE_POLLING_INTERVAL);
        },

        visualizeMigrationState(state) {
            if (!state) {
                return;
            }

            if (this.step !== state.step) {
                // step change, reset progress bar to zero without animation
                this.progress = 0;
            }
            this.step = state.step;

            this.$nextTick(() => {
                // needs to happen one tick later, to reset the progress bar if a step change occurred
                this.progress = state.progress;
                this.total = state.total;
            });

            if (
                state.step === MIGRATION_STEP.FETCHING ||
                state.step === MIGRATION_STEP.WRITING ||
                state.step === MIGRATION_STEP.MEDIA_PROCESSING ||
                state.step === MIGRATION_STEP.ABORTING ||
                state.step === MIGRATION_STEP.CLEANUP ||
                state.step === MIGRATION_STEP.INDEXING
            ) {
                this.componentIndex = UI_COMPONENT_INDEX.LOADING_SCREEN;
                this.flowChartItemIndex = MIGRATION_STEP_DISPLAY_INDEX[state.step];
            } else if (
                state.step === MIGRATION_STEP.WAITING_FOR_APPROVE ||
                state.step === MIGRATION_STEP.IDLE
            ) {
                this.componentIndex = UI_COMPONENT_INDEX.RESULT_SUCCESS;
                this.flowChartItemIndex = MIGRATION_STEP_DISPLAY_INDEX[state.step];
                this.unregisterPolling();
            }

            // update flow chart
            if (state.step !== MIGRATION_STEP.WAITING_FOR_APPROVE && state.step !== MIGRATION_STEP.IDLE) {
                this.flowChartItemVariant = 'info';
            } else {
                this.flowChartItemVariant = 'success';
            }
            if (this.flowChartInitialItemVariants.length < this.flowChartItemIndex) {
                while (this.flowChartInitialItemVariants.length < this.flowChartItemIndex) {
                    this.flowChartInitialItemVariants.push('success');
                }
            }
        },

        async startMigration() {
            try {
                await this.migrationApiService.startMigration(this.dataSelectionIds);
            } catch (e) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.startMigration'),
                });
                this.$router.push({
                    name: 'swag.migration.index.main',
                    query: {
                        forceFullStateReload: true, // also resets data selection for next run
                    },
                });
            }
        },

        async migrationStatePoller() {
            try {
                const state = await this.migrationApiService.getState();
                if (state && state.step === MIGRATION_STEP.IDLE) {
                    // back in idle, which happens after aborting for example
                    this.unregisterPolling();
                    this.$router.push({
                        name: 'swag.migration.index.main',
                        query: {
                            forceFullStateReload: true, // also resets data selection for next run
                        },
                    });
                }
                this.visualizeMigrationState(state);
            } catch (e) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.getState'),
                });
            }

            return Promise.resolve();
        },

        async approveFinishedMigration() {
            try {
                State.commit('swagMigration/setIsLoading', true);
                await this.migrationApiService.approveFinishedMigration();
                this.$router.push({
                    name: 'swag.migration.index.main',
                    query: {
                        forceFullStateReload: true, // also resets data selection for next run
                    },
                });
            } catch (e) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.approveFinishedMigration'),
                });
                this.$router.push({
                    name: 'swag.migration.index.main',
                    query: {
                        forceFullStateReload: true, // also resets data selection for next run
                    },
                });
            } finally {
                State.commit('swagMigration/setIsLoading', false);
            }
        },

        async onAbortButtonClick() {
            this.showAbortMigrationConfirmDialog = true;
        },

        onCloseAbortMigrationConfirmDialog() {
            this.showAbortMigrationConfirmDialog = false;
        },

        async onAbort() {
            try {
                this.showAbortMigrationConfirmDialog = false;
                State.commit('swagMigration/setIsLoading', true);
                await this.migrationApiService.abortMigration();
                const state = await this.migrationApiService.getState();
                this.visualizeMigrationState(state);
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.abortMigration'),
                });
            } finally {
                State.commit('swagMigration/setIsLoading', false);
            }
        },

        onContinueButtonClick() {
            if (this.componentIndex === UI_COMPONENT_INDEX.RESULT_SUCCESS) {
                return this.approveFinishedMigration();
            }

            return Promise.resolve();
        },
    },
});
