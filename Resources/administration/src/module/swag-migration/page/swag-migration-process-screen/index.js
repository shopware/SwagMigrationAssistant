import template from './swag-migration-process-screen.html.twig';
import './swag-migration-process-screen.scss';
import { UI_COMPONENT_INDEX } from '../../../../core/data/MigrationUIStore';
import { MIGRATION_STATUS } from '../../../../core/service/migration/swag-migration-worker-status-manager.service';
import {
    MIGRATION_ACCESS_TOKEN_NAME,
    WORKER_INTERRUPT_TYPE
} from '../../../../core/service/migration/swag-migration-worker.service';

const { Component, StateDeprecated } = Shopware;

Component.register('swag-migration-process-screen', {
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
            errorList: [],
            isTakeoverForbidden: false,
            isMigrationInterrupted: false,
            isOtherMigrationRunning: false,
            showAbortMigrationConfirmDialog: false,
            isPausedBeforeAbortDialog: false,
            /** @type MigrationProcessStore */
            migrationProcessStore: StateDeprecated.getStore('migrationProcess'),
            /** @type MigrationUIStore */
            migrationUIStore: StateDeprecated.getStore('migrationUI'),
            UI_COMPONENT_INDEX: UI_COMPONENT_INDEX,
            displayFlowChart: true,
            flowChartItemIndex: 0,
            flowChartItemVariant: 'info',
            flowChartInitialItemVariants: [],
            isWarningConfirmed: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        /**
         * @returns {string}
         */
        abortMigrationBackText() {
            if (this.isPausedBeforeAbortDialog) {
                return this.$tc('swag-migration.index.confirmAbortDialog.cancelPause');
            }

            return this.$tc('swag-migration.index.confirmAbortDialog.cancelRunning');
        },

        /**
         * @returns {boolean}
         */
        componentIndexIsResult() {
            return this.migrationUIStore.state.componentIndex === UI_COMPONENT_INDEX.RESULT_SUCCESS;
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
                    this.migrationUIStore.state.componentIndex !== UI_COMPONENT_INDEX.WARNING_CONFIRM &&
                    this.migrationProcessStore.state.isMigrating && !this.migrationUIStore.state.isPremappingValid) ||
                (this.migrationUIStore.state.componentIndex === UI_COMPONENT_INDEX.WARNING_CONFIRM &&
                    this.isWarningConfirmed === false);
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

    watch: {
        'migrationProcessStore.state.statusIndex': {
            immediate: true,
            /**
             * @param {number} status
             */
            handler(status) {
                if (this.migrationUIStore.state.isLoading) {
                    return;
                }

                if (status === MIGRATION_STATUS.WAITING) {
                    return;
                }

                if (status === MIGRATION_STATUS.PREMAPPING) {
                    this.$nextTick(() => {
                        this.flowChartItemIndex = status;
                    });
                    return;
                }

                this.$nextTick(() => {
                    this.flowChartItemIndex = status;

                    if (status !== MIGRATION_STATUS.FINISHED) {
                        this.flowChartItemVariant = 'info';
                    } else {
                        this.flowChartItemVariant = 'success';
                    }

                    if (this.flowChartInitialItemVariants.length < this.flowChartItemIndex) {
                        while (this.flowChartInitialItemVariants.length < this.flowChartItemIndex) {
                            this.flowChartInitialItemVariants.push('success');
                        }
                    }
                });

                if (status === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                    this.onProcessMediaFiles();
                }

                if (status === MIGRATION_STATUS.FINISHED) {
                    this.isOtherMigrationRunning = false;
                    this.onFinishWithoutErrors();
                }
            }
        },

        'migrationUIStore.state.isPremappingValid': {
            handler(valid) {
                if (valid) {
                    this.flowChartItemVariant = 'success';
                    return;
                }

                this.flowChartItemVariant = 'error';
            }
        }
    },

    created() {
        this.createdComponent();
    },

    beforeDestroy() {
        this.migrationWorkerService.unsubscribeInterrupt();
    },

    methods: {
        async createdComponent() {
            this.migrationUIStore.setIsLoading(true);

            let otherInstanceMigrating = this.migrationProcessStore.state.isMigrating;
            if (this.migrationProcessStore.state.isMigrating === false) {
                await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                    if (isRunning) {
                        otherInstanceMigrating = true;
                        this.isTakeoverForbidden = true;
                        this.onInvalidMigrationAccessToken();
                    }
                });

                if (!this.isTakeoverForbidden) {
                    await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                        if (runState.isMigrationAccessTokenValid === false && runState.isMigrationRunning === true) {
                            otherInstanceMigrating = true;
                            this.onInvalidMigrationAccessToken();
                            return;
                        }

                        this.migrationUIStore.setIsPaused(runState.isMigrationRunning);
                        if (this.migrationUIStore.state.isPaused) {
                            otherInstanceMigrating = true;
                            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.LOADING_SCREEN);
                        }

                        this.restoreFlowChart(runState.status);
                    });
                }
            }

            if (
                this.migrationProcessStore.state.connectionId === null
                || this.migrationProcessStore.state.environmentInformation === null
            ) {
                await this.migrationProcessStoreInitService.initProcessStore();
            }

            if (this.migrationProcessStore.state.connectionId === null) {
                this.$router.push({ name: 'swag.migration.wizard.introduction' });
                return;
            }

            // Do connection check
            this.migrationService.checkConnection(this.migrationProcessStore.state.connectionId)
                .then(async (connectionCheckResponse) => {
                    this.migrationProcessStore.setEnvironmentInformation(connectionCheckResponse);

                    if (
                        (
                            connectionCheckResponse.requestStatus.isWarning === false &&
                            connectionCheckResponse.requestStatus.code !== ''
                        ) ||
                        (!otherInstanceMigrating && !this.$route.params.startMigration)
                    ) {
                        this.$router.push({ name: 'swag.migration.index.main' });
                        return;
                    }

                    this.migrationWorkerService.restoreRunningMigration(false);

                    if (
                        (this.migrationProcessStore.state.isMigrating ||
                        this.migrationWorkerService.status === MIGRATION_STATUS.FINISHED) &&
                        !this.$route.params.startMigration
                    ) {
                        this.restoreRunningMigration();
                    }

                    if (this.$route.params.startMigration) {
                        await this.onMigrate();
                    }

                    this.migrationUIStore.setIsLoading(false);
                }).catch(() => {
                    this.migrationUIStore.setIsLoading(false);
                });
        },

        restoreFlowChart(currentStatus) {
            this.flowChartItemIndex = currentStatus;

            if (currentStatus !== MIGRATION_STATUS.FINISHED) {
                this.flowChartItemVariant = 'info';
            } else {
                this.flowChartItemVariant = 'success';
            }

            if (currentStatus === MIGRATION_STATUS.PREMAPPING && this.migrationUIStore.state.unfilledPremapping.length > 0) {
                this.flowChartItemVariant = 'error';
            }

            this.flowChartInitialItemVariants = [];
            for (let i = 0; i < currentStatus; i += 1) {
                this.flowChartInitialItemVariants.push('success');
            }
        },

        resetFlowChart() {
            this.flowChartItemIndex = 0;
            this.flowChartItemVariant = 'info';
            this.flowChartInitialItemVariants = [];
        },

        restoreRunningMigration() {
            this.displayFlowChart = true;

            // show loading or premapping screen
            if (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.PREMAPPING &&
                this.migrationUIStore.state.componentIndex === UI_COMPONENT_INDEX.WARNING_CONFIRM) {
                this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.WARNING_CONFIRM);
            } else if (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.PREMAPPING) {
                this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.PREMAPPING);
            } else if (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                this.onProcessMediaFiles();
            } else if (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.FINISHED) {
                this.onFinishWithoutErrors();
            } else {
                this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.LOADING_SCREEN);
            }

            if (this.migrationProcessStore.state.statusIndex !== MIGRATION_STATUS.WAITING) {
                this.restoreFlowChart(this.migrationProcessStore.state.statusIndex);
            }

            // subscribe to the interrupt event again
            this.migrationWorkerService.subscribeInterrupt(this.onInterrupt);
        },

        onAbortButtonClick() {
            this.isOtherMigrationRunning = false;

            if (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.PREMAPPING) {
                this.migrationUIStore.setIsLoading(true);
                this.onInterrupt(WORKER_INTERRUPT_TYPE.STOP);
                return;
            }

            if (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.FETCH_DATA) {
                this.migrationUIStore.setIsLoading(true);
                this.migrationWorkerService.stopMigration();
                return;
            }

            this.showAbortMigrationConfirmDialog = true;
            this.isPausedBeforeAbortDialog = this.migrationUIStore.state.isPaused;

            if (!this.migrationUIStore.state.isPaused) {
                this.migrationUIStore.setIsLoading(true);
                this.migrationWorkerService.pauseMigration();
            }
        },

        onBackButtonClick() {
            this.migrationWorkerService.status = MIGRATION_STATUS.WAITING;
            this.migrationProcessStore.setIsMigrating(false);
            this.isOtherMigrationRunning = false;
            this.$router.push({ name: 'swag.migration.index.main' });
        },

        onStartButtonClick() {
            if (this.migrationUIStore.state.componentIndex === UI_COMPONENT_INDEX.WARNING_CONFIRM) {
                this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.PREMAPPING);
                return;
            }

            this.migrationUIStore.setIsLoading(true);
            this.migrationService.writePremapping(
                this.migrationProcessStore.state.runId,
                this.migrationUIStore.state.premapping
            ).then(() => {
                this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.LOADING_SCREEN);
                this.migrationUIStore.setIsLoading(false);
                this.migrationWorkerService.startMigration(
                    this.migrationProcessStore.state.runId
                ).then(() => {
                    this.migrationUIStore.setIsLoading(false);
                }).catch(() => {
                    this.onInvalidMigrationAccessToken();
                });
            });
        },

        onPauseButtonClick() {
            this.migrationWorkerService.pauseMigration();
            this.migrationProcessStore.setIsMigrating(false);
            this.migrationUIStore.setIsLoading(true);
        },

        async onContinueButtonClick() {
            this.migrationUIStore.setIsLoading(true);
            this.isOtherMigrationRunning = false;
            this.migrationProcessStore.setIsMigrating(true);

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    this.migrationUIStore.setIsLoading(false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (!this.isTakeoverForbidden) {
                await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                    if (runState.requestErrorCode !== undefined &&
                        runState.requestErrorCode !== '500') {
                        // Something is wrong with the connection
                        this.migrationUIStore.setIsLoading(false);
                        return;
                    }

                    this.migrationUIStore.setIsLoading(false);
                    this.migrationUIStore.setIsPaused(false);

                    if (runState.isMigrationAccessTokenValid === false) {
                        this.onInterrupt(WORKER_INTERRUPT_TYPE.TAKEOVER);
                        return;
                    }

                    if (runState.isMigrationRunning === false) {
                        this.migrationProcessStore.setIsMigrating(false);
                        this.$router.push({ name: 'swag.migration.index.main' });
                        return;
                    }

                    this.migrationWorkerService.restoreRunningMigration();
                    this.restoreRunningMigration();
                });
            }
        },

        async onMigrate() {
            this.isOtherMigrationRunning = false;

            this.$nextTick().then(async () => {
                this.resetFlowChart();
                this.migrationProcessStore.setIsMigrating(true);
                this.errorList = [];

                // show loading screen
                this.migrationUIStore.setIsLoading(true);
                this.migrationProcessStore.resetProgress();

                let isMigrationRunningInOtherTab = false;
                await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                    isMigrationRunningInOtherTab = isRunning;
                });

                if (isMigrationRunningInOtherTab) {
                    this.migrationUIStore.setIsLoading(false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                    return;
                }

                this.migrationWorkerService.subscribeInterrupt(this.onInterrupt.bind(this));

                await this.migrationWorkerService.createMigration(
                    this.migrationUIStore.state.dataSelectionIds
                ).then((runState) => {
                    this.migrationProcessStore.setEntityGroups(runState.runProgress);

                    if (
                        runState.isMigrationAccessTokenValid === false ||
                        runState.isMigrationRunning === true ||
                        runState.runUuid === null ||
                        runState.accessToken === null
                    ) {
                        this.onInvalidMigrationAccessToken();
                        return;
                    }

                    localStorage.setItem(MIGRATION_ACCESS_TOKEN_NAME, runState.accessToken);
                    this.migrationProcessStore.setRunId(runState.runUuid);

                    if (this.migrationProcessStore.state.environmentInformation.sourceSystemCurrency !== '' &&
                        this.migrationProcessStore.state.environmentInformation.targetSystemCurrency !== '' &&
                        this.migrationProcessStore.state.environmentInformation.sourceSystemCurrency !==
                            this.migrationProcessStore.state.environmentInformation.targetSystemCurrency) {
                        this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.WARNING_CONFIRM);
                    } else {
                        this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.PREMAPPING);
                    }

                    this.migrationUIStore.setIsLoading(false);
                });
            });
        },

        onFinishWithoutErrors() {
            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.RESULT_SUCCESS);
            this.$root.$emit('sales-channel-change');
            this.$root.$emit('on-change-notification-center-visibility', true);
        },

        onCloseAbortMigrationConfirmDialog() {
            this.showAbortMigrationConfirmDialog = false;

            if (!this.isPausedBeforeAbortDialog) {
                this.$nextTick(() => {
                    this.onContinueButtonClick();
                });
            }
        },

        /**
         * Check if a takeover is allowed, takeover migration and restore state
         */
        async onTakeoverMigration() {
            this.migrationProcessStore.setIsMigrating(true);
            this.migrationUIStore.setIsLoading(true);

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    this.migrationUIStore.setIsLoading(false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (this.isTakeoverForbidden) {
                return;
            }

            await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                if (runState.isMigrationRunning === false) {
                    this.migrationProcessStore.setIsMigrating(false);
                    this.migrationUIStore.setIsLoading(false);
                    this.isOtherMigrationRunning = false;
                    this.$router.push({ name: 'swag.migration.index.main' });
                    return;
                }

                this.migrationWorkerService.takeoverMigration(runState.runUuid).then(() => {
                    this.migrationUIStore.setIsLoading(false);
                    this.migrationWorkerService.restoreRunningMigration();
                    this.restoreRunningMigration();
                });
            });
        },

        /**
         * Abort the running migration on the other client so this client can start a new one.
         */
        async onAbortOtherMigration() {
            this.migrationUIStore.setIsLoading(true);
            this.migrationProcessStore.setIsMigrating(true);

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    this.migrationUIStore.setIsLoading(false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (this.isTakeoverForbidden) {
                return;
            }

            await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                if (runState.isMigrationRunning === false) {
                    this.migrationProcessStore.setIsMigrating(false);
                    this.migrationUIStore.setIsLoading(false);
                    this.isOtherMigrationRunning = false;
                    this.$router.push({ name: 'swag.migration.index.main' });
                    return;
                }

                this.migrationService.abortMigration(runState.runUuid).then(() => {
                    this.migrationProcessStore.setIsMigrating(false);
                    this.migrationUIStore.setIsLoading(false);
                    this.isOtherMigrationRunning = false;
                    this.$router.push({ name: 'swag.migration.index.main' });
                });
            });
        },

        /**
         * If the current migration was interrupted through a takeover, pause or stop
         *
         * @param {number} type
         */
        onInterrupt(type) {
            if (type === WORKER_INTERRUPT_TYPE.TAKEOVER) {
                this.onConfiscatedMigration();
            } else if (type === WORKER_INTERRUPT_TYPE.STOP) {
                this.onStop();
            } else if (type === WORKER_INTERRUPT_TYPE.PAUSE) {
                this.onPause();
            } else if (type === WORKER_INTERRUPT_TYPE.CONNECTION_LOST) {
                this.onConnectionLost();
            }
        },

        /**
         * If the current migration was confiscated by a takeover from another client
         */
        onConfiscatedMigration() {
            this.onInvalidMigrationAccessToken();
            this.isMigrationInterrupted = true;
            this.$nextTick(() => {
                this.$refs.loadingScreenTakeover.refreshState();
            });
        },

        /**
         * If the current migration was stopped
         */
        onStop() {
            this.migrationService.abortMigration(this.migrationProcessStore.state.runId).then(() => {
                this.showAbortMigrationConfirmDialog = false;
                this.isMigrationInterrupted = false;
                this.migrationProcessStore.setIsMigrating(false);
                this.migrationUIStore.setIsPaused(false);
                this.migrationUIStore.setIsLoading(false);
                this.$nextTick(() => {
                    this.$router.push({ name: 'swag.migration.index.main' });
                });
            });
        },

        /**
         * If the current migration was paused
         */
        onPause() {
            this.isMigrationInterrupted = false;
            this.migrationProcessStore.setIsMigrating(false);
            this.migrationUIStore.setIsPaused(true);
            this.migrationUIStore.setIsLoading(false);
            this.isOtherMigrationRunning = false;
            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.PAUSE_SCREEN);
        },

        onConnectionLost() {
            this.migrationProcessStore.setIsMigrating(false);
            this.migrationUIStore.setIsPaused(false);
            this.migrationUIStore.setDataSelectionIds([]);
            this.migrationUIStore.setDataSelectionTableData([]);
            this.migrationUIStore.setIsLoading(false);
            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.CONNECTION_LOST);
        },

        /**
         * If the current accessToken is invalid and a migration is running
         */
        onInvalidMigrationAccessToken() {
            this.displayFlowChart = false;
            this.isMigrationInterrupted = false;
            this.migrationProcessStore.setIsMigrating(false);
            this.migrationUIStore.setIsPaused(false);
            this.isOtherMigrationRunning = true;
            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.TAKEOVER);
            this.migrationUIStore.setIsLoading(false);
        },

        onWarningConfirmationChanged(confirmed) {
            this.isWarningConfirmed = confirmed;
        },

        onProcessMediaFiles() {
            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.MEDIA_SCREEN);
        }
    }
});
