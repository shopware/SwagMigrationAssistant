import template from './swag-migration-process-screen.html.twig';
import './swag-migration-process-screen.scss';
import { UI_COMPONENT_INDEX } from '../../../../core/data/migrationUI.store';
import { MIGRATION_STATUS } from '../../../../core/service/migration/swag-migration-worker-status-manager.service';
import {
    MIGRATION_ACCESS_TOKEN_NAME,
    WORKER_INTERRUPT_TYPE
} from '../../../../core/service/migration/swag-migration-worker.service';

const { Component } = Shopware;

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
            migrationProcessState: this.$store.state['swagMigration/process'],
            migrationUIState: this.$store.state['swagMigration/ui'],
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
        statusIndex() {
            return this.migrationProcessState.statusIndex;
        },

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
            return this.migrationUIState.componentIndex === UI_COMPONENT_INDEX.RESULT_SUCCESS;
        },

        /**
         * @returns {boolean}
         */
        abortButtonVisible() {
            return this.migrationUIState.isPaused || (
                this.migrationProcessState.isMigrating &&
                !this.migrationUIState.isLoading &&
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
            return (!this.migrationProcessState.isMigrating && !this.migrationUIState.isPaused) ||
                (this.migrationProcessState.statusIndex === MIGRATION_STATUS.FETCH_DATA &&
                    this.migrationProcessState.isMigrating) ||
                (this.componentIndexIsResult && this.migrationProcessState.isMigrating);
        },

        isFetching() {
            return this.migrationProcessState.statusIndex === MIGRATION_STATUS.FETCH_DATA &&
                this.migrationProcessState.isMigrating;
        },

        /**
         * @returns {boolean}
         */
        migrateButtonDisabled() {
            return this.migrationUIState.isLoading ||
                this.isFetching ||
                this.componentIndexIsResult;
        },

        /**
         * @returns {boolean}
         */
        startButtonVisible() {
            return !this.migrationUIState.isLoading &&
                this.migrationProcessState.statusIndex === MIGRATION_STATUS.PREMAPPING &&
                this.migrationProcessState.isMigrating;
        },

        premappingIsNotReady() {
            return this.migrationProcessState.statusIndex === MIGRATION_STATUS.PREMAPPING &&
                this.migrationUIState.componentIndex !== UI_COMPONENT_INDEX.WARNING_CONFIRM &&
                this.migrationProcessState.isMigrating &&
                !this.migrationUIState.isPremappingValid;
        },

        warningIsNotReady() {
            return this.migrationUIState.componentIndex === UI_COMPONENT_INDEX.WARNING_CONFIRM &&
                this.isWarningConfirmed === false;
        },

        /**
         * @returns {boolean}
         */
        startButtonDisabled() {
            return this.migrationUIState.isLoading ||
                this.premappingIsNotReady ||
                this.warningIsNotReady;
        },

        /**
         * @returns {boolean}
         */
        pauseButtonVisible() {
            return this.migrationProcessState.isMigrating &&
                !this.migrationUIState.isPaused &&
                !this.componentIndexIsResult &&
                [
                    MIGRATION_STATUS.WAITING,
                    MIGRATION_STATUS.FETCH_DATA,
                    MIGRATION_STATUS.PREMAPPING
                ].includes(this.migrationProcessState.statusIndex) === false;
        },

        /**
         * @returns {boolean}
         */
        pauseButtonDisabled() {
            return this.migrationUIState.isLoading;
        },

        /**
         * @returns {boolean}
         */
        continueButtonVisible() {
            return this.migrationUIState.isPaused;
        }
    },

    watch: {
        statusIndex: {
            immediate: true,
            /**
             * @param {number} status
             */
            handler(status) {
                if (this.migrationUIState.isLoading) {
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

        'migrationUIState.isPremappingValid': {
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
            this.$store.commit('swagMigration/ui/setIsLoading', true);

            let otherInstanceMigrating = this.migrationProcessState.isMigrating;
            if (this.migrationProcessState.isMigrating === false) {
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

                        this.$store.commit('swagMigration/ui/setIsPaused', runState.isMigrationRunning);
                        if (this.migrationUIState.isPaused) {
                            otherInstanceMigrating = true;
                            this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.LOADING_SCREEN);
                        }

                        this.restoreFlowChart(runState.status);
                    });
                }
            }

            if (
                this.migrationProcessState.connectionId === null
                || this.migrationProcessState.environmentInformation === null
            ) {
                await this.migrationProcessStoreInitService.initProcessStore();
            }

            if (this.migrationProcessState.connectionId === null) {
                this.$router.push({ name: 'swag.migration.wizard.introduction' });
                return;
            }

            // Do connection check
            this.migrationService.checkConnection(this.migrationProcessState.connectionId)
                .then(async (connectionCheckResponse) => {
                    this.$store.commit('swagMigration/process/setEnvironmentInformation', connectionCheckResponse);

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
                        (this.migrationProcessState.isMigrating ||
                        this.migrationWorkerService.status === MIGRATION_STATUS.FINISHED) &&
                        !this.$route.params.startMigration
                    ) {
                        this.restoreRunningMigration();
                    }

                    if (this.$route.params.startMigration) {
                        await this.onMigrate();
                    }

                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                }).catch(() => {
                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                });
        },

        restoreFlowChart(currentStatus) {
            this.flowChartItemIndex = currentStatus;

            if (currentStatus !== MIGRATION_STATUS.FINISHED) {
                this.flowChartItemVariant = 'info';
            } else {
                this.flowChartItemVariant = 'success';
            }

            if (currentStatus === MIGRATION_STATUS.PREMAPPING && this.migrationUIState.unfilledPremapping.length > 0) {
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
            if (this.migrationProcessState.statusIndex === MIGRATION_STATUS.PREMAPPING &&
                this.migrationUIState.componentIndex === UI_COMPONENT_INDEX.WARNING_CONFIRM) {
                this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.WARNING_CONFIRM);
            } else if (this.migrationProcessState.statusIndex === MIGRATION_STATUS.PREMAPPING) {
                this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.PREMAPPING);
            } else if (this.migrationProcessState.statusIndex === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                this.onProcessMediaFiles();
            } else if (this.migrationProcessState.statusIndex === MIGRATION_STATUS.FINISHED) {
                this.onFinishWithoutErrors();
            } else {
                this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.LOADING_SCREEN);
            }

            if (this.migrationProcessState.statusIndex !== MIGRATION_STATUS.WAITING) {
                this.restoreFlowChart(this.migrationProcessState.statusIndex);
            }

            // subscribe to the interrupt event again
            this.migrationWorkerService.subscribeInterrupt(this.onInterrupt);
        },

        onAbortButtonClick() {
            this.isOtherMigrationRunning = false;

            if (this.migrationProcessState.statusIndex === MIGRATION_STATUS.PREMAPPING) {
                this.$store.commit('swagMigration/ui/setIsLoading', true);
                this.onInterrupt(WORKER_INTERRUPT_TYPE.STOP);
                return;
            }

            if (this.migrationProcessState.statusIndex === MIGRATION_STATUS.FETCH_DATA) {
                this.$store.commit('swagMigration/ui/setIsLoading', true);
                this.migrationWorkerService.stopMigration();
                return;
            }

            this.showAbortMigrationConfirmDialog = true;
            this.isPausedBeforeAbortDialog = this.migrationUIState.isPaused;

            if (!this.migrationUIState.isPaused) {
                this.$store.commit('swagMigration/ui/setIsLoading', true);
                this.migrationWorkerService.pauseMigration();
            }
        },

        onBackButtonClick() {
            this.migrationWorkerService.status = MIGRATION_STATUS.WAITING;
            this.$store.commit('swagMigration/process/setIsMigrating', false);
            this.isOtherMigrationRunning = false;
            this.$router.push({ name: 'swag.migration.index.main' });
        },

        onStartButtonClick() {
            if (this.migrationUIState.componentIndex === UI_COMPONENT_INDEX.WARNING_CONFIRM) {
                this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.PREMAPPING);
                return;
            }

            this.$store.commit('swagMigration/ui/setIsLoading', true);
            this.migrationService.writePremapping(
                this.migrationProcessState.runId,
                this.migrationUIState.premapping
            ).then(() => {
                this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.LOADING_SCREEN);
                this.$store.commit('swagMigration/ui/setIsLoading', false);
                this.migrationWorkerService.startMigration(
                    this.migrationProcessState.runId
                ).then(() => {
                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                }).catch(() => {
                    this.onInvalidMigrationAccessToken();
                });
            });
        },

        onPauseButtonClick() {
            this.migrationWorkerService.pauseMigration();
            this.$store.commit('swagMigration/process/setIsMigrating', false);
            this.$store.commit('swagMigration/ui/setIsLoading', true);
        },

        async onContinueButtonClick() {
            this.$store.commit('swagMigration/ui/setIsLoading', true);
            this.isOtherMigrationRunning = false;
            this.$store.commit('swagMigration/process/setIsMigrating', true);

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (!this.isTakeoverForbidden) {
                await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                    if (runState.requestErrorCode !== undefined &&
                        runState.requestErrorCode !== '500') {
                        // Something is wrong with the connection
                        this.$store.commit('swagMigration/ui/setIsLoading', false);
                        return;
                    }

                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                    this.$store.commit('swagMigration/ui/setIsPaused', false);

                    if (runState.isMigrationAccessTokenValid === false) {
                        this.onInterrupt(WORKER_INTERRUPT_TYPE.TAKEOVER);
                        return;
                    }

                    if (runState.isMigrationRunning === false) {
                        this.$store.commit('swagMigration/process/setIsMigrating', false);
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
                this.$store.commit('swagMigration/process/setIsMigrating', true);
                this.errorList = [];

                // show loading screen
                this.$store.commit('swagMigration/ui/setIsLoading', true);
                this.$store.commit('swagMigration/process/resetProgress');

                let isMigrationRunningInOtherTab = false;
                await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                    isMigrationRunningInOtherTab = isRunning;
                });

                if (isMigrationRunningInOtherTab) {
                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                    return;
                }

                this.migrationWorkerService.subscribeInterrupt(this.onInterrupt.bind(this));

                await this.migrationWorkerService.createMigration(
                    this.migrationUIState.dataSelectionIds
                ).then((runState) => {
                    this.$store.commit('swagMigration/process/setEntityGroups', runState.runProgress);

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
                    this.$store.commit('swagMigration/process/setRunId', runState.runUuid);

                    if (this.migrationProcessState.environmentInformation.sourceSystemCurrency !== '' &&
                        this.migrationProcessState.environmentInformation.targetSystemCurrency !== '' &&
                        this.migrationProcessState.environmentInformation.sourceSystemCurrency !==
                            this.migrationProcessState.environmentInformation.targetSystemCurrency) {
                        this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.WARNING_CONFIRM);
                    } else {
                        this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.PREMAPPING);
                    }

                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                });
            });
        },

        onFinishWithoutErrors() {
            this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.RESULT_SUCCESS);
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
            this.$store.commit('swagMigration/process/setIsMigrating', true);
            this.$store.commit('swagMigration/ui/setIsLoading', true);

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (this.isTakeoverForbidden) {
                return;
            }

            await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                if (runState.isMigrationRunning === false) {
                    this.$store.commit('swagMigration/process/setIsMigrating', false);
                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                    this.isOtherMigrationRunning = false;
                    this.$router.push({ name: 'swag.migration.index.main' });
                    return;
                }

                this.migrationWorkerService.takeoverMigration(runState.runUuid).then(() => {
                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                    this.migrationWorkerService.restoreRunningMigration();
                    this.restoreRunningMigration();
                });
            });
        },

        /**
         * Abort the running migration on the other client so this client can start a new one.
         */
        async onAbortOtherMigration() {
            this.$store.commit('swagMigration/ui/setIsLoading', true);
            this.$store.commit('swagMigration/process/setIsMigrating', true);

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (this.isTakeoverForbidden) {
                return;
            }

            await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                if (runState.isMigrationRunning === false) {
                    this.$store.commit('swagMigration/process/setIsMigrating', false);
                    this.$store.commit('swagMigration/ui/setIsLoading', false);
                    this.isOtherMigrationRunning = false;
                    this.$router.push({ name: 'swag.migration.index.main' });
                    return;
                }

                this.migrationService.abortMigration(runState.runUuid).then(() => {
                    this.$store.commit('swagMigration/process/setIsMigrating', false);
                    this.$store.commit('swagMigration/ui/setIsLoading', false);
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
            this.migrationService.abortMigration(this.migrationProcessState.runId).then(() => {
                this.showAbortMigrationConfirmDialog = false;
                this.isMigrationInterrupted = false;
                this.$store.commit('swagMigration/process/setIsMigrating', false);
                this.$store.commit('swagMigration/ui/setIsPaused', false);
                this.$store.commit('swagMigration/ui/setIsLoading', false);
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
            this.$store.commit('swagMigration/process/setIsMigrating', false);
            this.$store.commit('swagMigration/ui/setIsPaused', true);
            this.$store.commit('swagMigration/ui/setIsLoading', false);
            this.isOtherMigrationRunning = false;
            this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.PAUSE_SCREEN);
        },

        onConnectionLost() {
            this.$store.commit('swagMigration/process/setIsMigrating', false);
            this.$store.commit('swagMigration/ui/setIsPaused', false);
            this.$store.commit('swagMigration/ui/setDataSelectionIds', []);
            this.$store.commit('swagMigration/ui/setDataSelectionTableData', []);
            this.$store.commit('swagMigration/ui/setIsLoading', false);
            this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.CONNECTION_LOST);
        },

        /**
         * If the current accessToken is invalid and a migration is running
         */
        onInvalidMigrationAccessToken() {
            this.displayFlowChart = false;
            this.isMigrationInterrupted = false;
            this.$store.commit('swagMigration/process/setIsMigrating', false);
            this.$store.commit('swagMigration/ui/setIsPaused', false);
            this.isOtherMigrationRunning = true;
            this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.TAKEOVER);
            this.$store.commit('swagMigration/ui/setIsLoading', false);
        },

        onWarningConfirmationChanged(confirmed) {
            this.isWarningConfirmed = confirmed;
        },

        onProcessMediaFiles() {
            this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.MEDIA_SCREEN);
        }
    }
});
