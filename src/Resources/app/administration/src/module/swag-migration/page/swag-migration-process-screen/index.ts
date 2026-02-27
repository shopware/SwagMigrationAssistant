import template from './swag-migration-process-screen.html.twig';
import './swag-migration-process-screen.scss';
import { MIGRATION_API_SERVICE, MIGRATION_STEP } from '../../../../core/service/api/swag-migration.api.service';
import type { MigrationState } from '../../../../type/types';
import type { MigrationStore } from '../../store/migration.store';
import { MIGRATION_STORE_ID } from '../../store/migration.store';

const { Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

/**
 * @private
 */
export const MIGRATION_STATE_POLLING_INTERVAL = 1000 as const; // 1 second

/**
 * @private
 */
export const MIGRATION_STEP_DISPLAY_INDEX = {
    [MIGRATION_STEP.IDLE]: 0,
    [MIGRATION_STEP.FETCHING]: 0,
    [MIGRATION_STEP.ERROR_RESOLUTION]: 1,
    [MIGRATION_STEP.WRITING]: 2,
    [MIGRATION_STEP.MEDIA_PROCESSING]: 3,
    [MIGRATION_STEP.ABORTING]: 4,
    [MIGRATION_STEP.CLEANUP]: 4,
    [MIGRATION_STEP.INDEXING]: 5,
    [MIGRATION_STEP.WAITING_FOR_APPROVE]: 6,
} as const;

/**
 * @private
 */
export const UI_COMPONENT_INDEX = {
    LOADING_SCREEN: 0,
    ERROR_RESOLUTION: 1,
    RESULT_SUCCESS: 2,
} as const;

/**
 * @private
 */
export interface SwagMigrationProcessScreenData {
    displayFlowChart: boolean;
    flowChartItemIndex: number;
    flowChartItemVariant: string;
    flowChartInitialItemVariants: string[];
    UI_COMPONENT_INDEX: typeof UI_COMPONENT_INDEX;
    componentIndex: number;
    showAbortMigrationConfirmDialog: boolean;
    pollingIntervalId: number | null;
    step: MigrationState['step'];
    progress: number;
    total: number;
    migrationStore: MigrationStore;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
        'acl',
    ],

    mixins: [
        'notification',
    ],

    metaInfo() {
        if (this.step === MIGRATION_STEP.ERROR_RESOLUTION) {
            return {
                title: this.errorResolutionMetaTitle,
            };
        }

        return {
            title:
                this.progressPercentage !== null
                    ? `${this.progressPercentage}% ${this.$createTitle()}`
                    : this.$createTitle(),
        };
    },

    data(): SwagMigrationProcessScreenData {
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
            migrationStore: Shopware.Store.get(MIGRATION_STORE_ID),
        };
    },

    computed: {
        ...mapState(
            () => Store.get('swagMigration'),
            [
                'isLoading',
                'dataSelectionIds',
            ],
        ),

        errorResolutionMetaTitle() {
            const stepName = this.$tc('swag-migration.index.error-resolution.step.header.title');
            const adminName = this.$tc('global.sw-admin-menu.textShopwareAdmin');

            return `${stepName} | ${adminName}`;
        },

        abortButtonVisible() {
            return !this.isLoading && !this.componentIndexIsResult;
        },

        backToOverviewButtonVisible() {
            return !this.isLoading && this.componentIndex === UI_COMPONENT_INDEX.RESULT_SUCCESS;
        },

        backToOverviewButtonDisabled() {
            return this.isLoading;
        },

        abortButtonDisabled() {
            return this.isLoading || this.step === MIGRATION_STEP.ABORTING || !this.acl.can('swag_migration.editor');
        },

        componentIndexIsResult() {
            return this.componentIndex === UI_COMPONENT_INDEX.RESULT_SUCCESS;
        },

        progressPercentage() {
            if (!this.total) {
                return null;
            }

            // Prevent progress bar and window title from exceeding 100%
            return Math.min(Math.round((this.progress / this.total) * 100), 100);
        },
    },

    unmounted() {
        this.unmountedComponent();
    },

    methods: {
        async createdComponent() {
            await this.initState();
            this.migrationStore.setIsLoading(true);

            if (this.connectionId === null) {
                await this.$router.push({ name: 'swag.migration.index.main' });
                return;
            }

            let migrationRunning = false;
            try {
                const state = await this.migrationApiService.getState();

                if (state?.step !== MIGRATION_STEP.IDLE) {
                    migrationRunning = true;
                    this.visualizeMigrationState(state);
                }
            } catch {
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
                } catch {
                    this.createNotificationError({
                        title: this.$tc('global.default.error'),
                        message: this.$tc('swag-migration.api-error.getState'),
                    });
                }
            }

            this.registerPolling();
            this.migrationStore.setIsLoading(false);
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

        visualizeMigrationState(state: MigrationState | null) {
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
            } else if (state.step === MIGRATION_STEP.ERROR_RESOLUTION) {
                this.componentIndex = UI_COMPONENT_INDEX.ERROR_RESOLUTION;
                this.flowChartItemIndex = MIGRATION_STEP_DISPLAY_INDEX[state.step];
            } else if (state.step === MIGRATION_STEP.WAITING_FOR_APPROVE || state.step === MIGRATION_STEP.IDLE) {
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
            } catch {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.startMigration'),
                });

                await this.$router.push({
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
                    await this.$router.push({
                        name: 'swag.migration.index.main',
                        query: {
                            forceFullStateReload: true, // also resets data selection for next run
                        },
                    });
                }

                this.visualizeMigrationState(state);
            } catch {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.getState'),
                });
            }

            return Promise.resolve();
        },

        async approveFinishedMigration() {
            try {
                this.migrationStore.setIsLoading(true);
                await this.migrationApiService.approveFinishedMigration();

                await this.$router.push({
                    name: 'swag.migration.index.main',
                    query: {
                        forceFullStateReload: true, // also resets data selection for next run
                    },
                });
            } catch {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.approveFinishedMigration'),
                });

                await this.$router.push({
                    name: 'swag.migration.index.main',
                    query: {
                        forceFullStateReload: true, // also resets data selection for next run
                    },
                });
            } finally {
                this.migrationStore.setIsLoading(false);
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
                this.migrationStore.setIsLoading(true);

                await this.migrationApiService.abortMigration();

                const state = await this.migrationApiService.getState();
                this.visualizeMigrationState(state);
            } catch {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.abortMigration'),
                });
            } finally {
                this.migrationStore.setIsLoading(false);
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
