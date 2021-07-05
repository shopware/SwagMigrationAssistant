import template from './swag-migration-process-screen.html.twig';
import './swag-migration-process-screen.scss';
import { UI_COMPONENT_INDEX } from '../../../../core/data/migrationUI.store';
import { MIGRATION_STATUS } from '../../../../core/service/migration/swag-migration-worker-status-manager.service';
import {
    MIGRATION_ACCESS_TOKEN_NAME,
    WORKER_INTERRUPT_TYPE,
} from '../../../../core/service/migration/swag-migration-worker.service';

const { Component, State, Mixin } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-migration-process-screen', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService',
        /** @var {MigrationWorkerService} migrationWorkerService */
        migrationWorkerService: 'migrationWorkerService',
        /** @var {MigrationProcessStoreInitService} migrationProcessStoreInitService */
        migrationProcessStoreInitService: 'processStoreInitService',
        /** @var {MigrationUiStoreInitService} migrationUiStoreInitService */
        migrationUiStoreInitService: 'uiStoreInitService',
    },

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            errorList: [],
            isTakeoverForbidden: false,
            isMigrationInterrupted: false,
            isOtherMigrationRunning: false,
            showAbortMigrationConfirmDialog: false,
            isPausedBeforeAbortDialog: false,
            UI_COMPONENT_INDEX: UI_COMPONENT_INDEX,
            displayFlowChart: true,
            flowChartItemIndex: 0,
            flowChartItemVariant: 'info',
            flowChartInitialItemVariants: [],
            isWarningConfirmed: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        ...mapState('swagMigration/process', [
            'statusIndex',
            'isMigrating',
            'connectionId',
            'environmentInformation',
            'runId',
        ]),

        ...mapState('swagMigration/ui', [
            'componentIndex',
            'isPaused',
            'isLoading',
            'isPremappingValid',
            'unfilledPremapping',
            'premapping',
            'dataSelectionIds',
        ]),

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
            return this.componentIndex === UI_COMPONENT_INDEX.RESULT_SUCCESS;
        },

        /**
         * @returns {boolean}
         */
        abortButtonVisible() {
            return this.isPaused || (
                this.isMigrating &&
                !this.isLoading &&
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
            return (!this.isMigrating && !this.isPaused) ||
                (this.statusIndex === MIGRATION_STATUS.FETCH_DATA &&
                    this.isMigrating) ||
                (this.componentIndexIsResult && this.isMigrating);
        },

        isFetching() {
            return this.statusIndex === MIGRATION_STATUS.FETCH_DATA &&
                this.isMigrating;
        },

        /**
         * @returns {boolean}
         */
        migrateButtonDisabled() {
            return this.isLoading ||
                this.isFetching ||
                this.componentIndexIsResult;
        },

        /**
         * @returns {boolean}
         */
        startButtonVisible() {
            return !this.isLoading &&
                this.statusIndex === MIGRATION_STATUS.PREMAPPING &&
                this.isMigrating;
        },

        premappingIsNotReady() {
            return this.statusIndex === MIGRATION_STATUS.PREMAPPING &&
                this.componentIndex !== UI_COMPONENT_INDEX.WARNING_CONFIRM &&
                this.isMigrating &&
                !this.isPremappingValid;
        },

        warningIsNotReady() {
            return this.componentIndex === UI_COMPONENT_INDEX.WARNING_CONFIRM &&
                this.isWarningConfirmed === false;
        },

        /**
         * @returns {boolean}
         */
        startButtonDisabled() {
            return this.isLoading ||
                this.premappingIsNotReady ||
                this.warningIsNotReady;
        },

        /**
         * @returns {boolean}
         */
        pauseButtonVisible() {
            return this.isMigrating &&
                !this.isPaused &&
                !this.componentIndexIsResult &&
                [
                    MIGRATION_STATUS.WAITING,
                    MIGRATION_STATUS.PREMAPPING,
                ].includes(this.statusIndex) === false;
        },

        /**
         * @returns {boolean}
         */
        pauseButtonDisabled() {
            return this.isLoading;
        },

        /**
         * @returns {boolean}
         */
        continueButtonVisible() {
            return this.isPaused;
        },
    },

    watch: {
        statusIndex: {
            immediate: true,
            /**
             * @param {number} status
             */
            handler(status) {
                if (this.isLoading) {
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
            },
        },

        isPremappingValid: {
            handler(valid) {
                if (valid) {
                    this.flowChartItemVariant = 'success';
                    return;
                }

                this.flowChartItemVariant = 'error';
            },
        },
    },

    created() {
        this.createdComponent();
    },

    beforeDestroy() {
        this.beforeDestroyedComponent();
    },

    methods: {
        async createdComponent() {
            State.commit('swagMigration/ui/setIsLoading', true);

            let otherInstanceMigrating = this.isMigrating;
            if (this.isMigrating === false) {
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

                        State.commit('swagMigration/ui/setIsPaused', runState.isMigrationRunning);
                        if (this.isPaused) {
                            otherInstanceMigrating = true;
                            State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.LOADING_SCREEN);
                        }

                        this.restoreFlowChart(runState.status);
                    });
                }
            }

            if (
                this.connectionId === null
                || this.environmentInformation === null
            ) {
                await this.migrationProcessStoreInitService.initProcessStore();
            }

            if (this.connectionId === null) {
                this.$router.push({ name: 'swag.migration.wizard.introduction' });
                return Promise.resolve();
            }

            // Do connection check
            return this.migrationService.checkConnection(this.connectionId)
                .then(async (connectionCheckResponse) => {
                    State.commit('swagMigration/process/setEnvironmentInformation', connectionCheckResponse);

                    if (
                        (
                            connectionCheckResponse.requestStatus.isWarning === false &&
                            connectionCheckResponse.requestStatus.code !== ''
                        ) ||
                        (!otherInstanceMigrating && !this.$route.params.startMigration)
                    ) {
                        this.$router.push({ name: 'swag.migration.index.main' });
                        return Promise.resolve();
                    }

                    this.migrationWorkerService.restoreRunningMigration(false);

                    if (
                        (this.isMigrating ||
                        this.migrationWorkerService.status === MIGRATION_STATUS.FINISHED) &&
                        !this.$route.params.startMigration
                    ) {
                        this.restoreRunningMigration();
                    }

                    if (this.$route.params.startMigration) {
                        await this.onMigrate();
                    }

                    State.commit('swagMigration/ui/setIsLoading', false);

                    return Promise.resolve();
                }).catch(() => {
                    State.commit('swagMigration/ui/setIsLoading', false);
                });
        },

        beforeDestroyedComponent() {
            this.migrationWorkerService.unsubscribeInterrupt();
        },

        restoreFlowChart(currentStatus) {
            this.flowChartItemIndex = currentStatus;

            if (currentStatus !== MIGRATION_STATUS.FINISHED) {
                this.flowChartItemVariant = 'info';
            } else {
                this.flowChartItemVariant = 'success';
            }

            if (currentStatus === MIGRATION_STATUS.PREMAPPING && this.unfilledPremapping.length > 0) {
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
            if (this.statusIndex === MIGRATION_STATUS.PREMAPPING &&
                this.componentIndex === UI_COMPONENT_INDEX.WARNING_CONFIRM) {
                State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.WARNING_CONFIRM);
            } else if (this.statusIndex === MIGRATION_STATUS.PREMAPPING) {
                State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.PREMAPPING);
            } else if (this.statusIndex === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                this.onProcessMediaFiles();
            } else if (this.statusIndex === MIGRATION_STATUS.FINISHED) {
                this.onFinishWithoutErrors();
            } else {
                State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.LOADING_SCREEN);
            }

            if (this.statusIndex !== MIGRATION_STATUS.WAITING) {
                this.restoreFlowChart(this.statusIndex);
            }

            // subscribe to the interrupt event again
            this.migrationWorkerService.subscribeInterrupt(this.onInterrupt);
        },

        onAbortButtonClick() {
            this.isOtherMigrationRunning = false;

            if (this.statusIndex === MIGRATION_STATUS.PREMAPPING) {
                State.commit('swagMigration/ui/setIsLoading', true);
                this.onInterrupt(WORKER_INTERRUPT_TYPE.STOP);
                return;
            }

            this.showAbortMigrationConfirmDialog = true;
            this.isPausedBeforeAbortDialog = this.isPaused;

            if (!this.isPaused) {
                State.commit('swagMigration/ui/setIsLoading', true);
                this.migrationWorkerService.pauseMigration();
            }
        },

        onSaveButtonClick() {
            this.migrationService.writePremapping(
                this.runId,
                this.premapping,
            ).then(() => {
                this.createNotificationSuccess({
                    message: this.$t('swag-migration.index.savePremapping.message'),
                    growl: true,
                });
            });
        },

        onBackButtonClick() {
            this.migrationWorkerService.status = MIGRATION_STATUS.WAITING;
            State.commit('swagMigration/process/setIsMigrating', false);
            this.isOtherMigrationRunning = false;
            this.$router.push({ name: 'swag.migration.index.main' });
        },

        onStartButtonClick() {
            if (this.componentIndex === UI_COMPONENT_INDEX.WARNING_CONFIRM) {
                State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.PREMAPPING);
                return Promise.resolve();
            }

            State.commit('swagMigration/ui/setIsLoading', true);
            return this.migrationService.writePremapping(
                this.runId,
                this.premapping,
            ).then(() => {
                State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.LOADING_SCREEN);
                State.commit('swagMigration/ui/setIsLoading', false);
                return this.migrationWorkerService.startMigration(
                    this.runId,
                ).then(() => {
                    State.commit('swagMigration/ui/setIsLoading', false);
                }).catch(() => {
                    this.onInvalidMigrationAccessToken();
                });
            });
        },

        onPauseButtonClick() {
            this.migrationWorkerService.pauseMigration();
            State.commit('swagMigration/process/setIsMigrating', false);
            State.commit('swagMigration/ui/setIsLoading', true);
        },

        async onContinueButtonClick() {
            State.commit('swagMigration/ui/setIsLoading', true);
            this.isOtherMigrationRunning = false;
            State.commit('swagMigration/process/setIsMigrating', true);

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    State.commit('swagMigration/ui/setIsLoading', false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (!this.isTakeoverForbidden) {
                await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                    if (runState.requestErrorCode !== undefined &&
                        runState.requestErrorCode !== '500') {
                        // Something is wrong with the connection
                        State.commit('swagMigration/ui/setIsLoading', false);
                        return;
                    }

                    State.commit('swagMigration/ui/setIsLoading', false);
                    State.commit('swagMigration/ui/setIsPaused', false);

                    if (runState.isMigrationAccessTokenValid === false) {
                        this.onInterrupt(WORKER_INTERRUPT_TYPE.TAKEOVER);
                        return;
                    }

                    if (runState.isMigrationRunning === false) {
                        State.commit('swagMigration/process/setIsMigrating', false);
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
                State.commit('swagMigration/process/setIsMigrating', true);
                this.errorList = [];

                // show loading screen
                State.commit('swagMigration/ui/setIsLoading', true);
                State.commit('swagMigration/process/resetProgress');

                let isMigrationRunningInOtherTab = false;
                await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                    isMigrationRunningInOtherTab = isRunning;
                });

                if (isMigrationRunningInOtherTab) {
                    State.commit('swagMigration/ui/setIsLoading', false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                    return;
                }

                this.migrationWorkerService.subscribeInterrupt(this.onInterrupt.bind(this));

                await this.migrationWorkerService.createMigration(
                    this.dataSelectionIds,
                ).then((runState) => {
                    State.commit('swagMigration/process/setEntityGroups', runState.runProgress);

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
                    State.commit('swagMigration/process/setRunId', runState.runUuid);

                    if (this.environmentInformation.sourceSystemCurrency !== '' &&
                        this.environmentInformation.targetSystemCurrency !== '' &&
                        this.environmentInformation.sourceSystemLocale !== '' &&
                        this.environmentInformation.targetSystemLocale !== '' &&
                        (
                            this.environmentInformation.sourceSystemCurrency !==
                            this.environmentInformation.targetSystemCurrency ||
                            this.environmentInformation.sourceSystemLocale !==
                            this.environmentInformation.targetSystemLocale
                        )
                    ) {
                        State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.WARNING_CONFIRM);
                    } else {
                        State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.PREMAPPING);
                    }

                    State.commit('swagMigration/ui/setIsLoading', false);
                });
            });
        },

        onFinishWithoutErrors() {
            State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.RESULT_SUCCESS);
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
            State.commit('swagMigration/process/setIsMigrating', true);
            State.commit('swagMigration/ui/setIsLoading', true);

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    State.commit('swagMigration/ui/setIsLoading', false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (this.isTakeoverForbidden) {
                return;
            }

            await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                if (runState.isMigrationRunning === false) {
                    State.commit('swagMigration/process/setIsMigrating', false);
                    State.commit('swagMigration/ui/setIsLoading', false);
                    this.isOtherMigrationRunning = false;
                    this.$router.push({ name: 'swag.migration.index.main' });
                    return;
                }

                this.migrationWorkerService.takeoverMigration(runState.runUuid).then(() => {
                    State.commit('swagMigration/ui/setIsLoading', false);
                    this.migrationWorkerService.restoreRunningMigration();
                    this.restoreRunningMigration();
                });
            });
        },

        /**
         * Abort the running migration on the other client so this client can start a new one.
         */
        async onAbortOtherMigration() {
            State.commit('swagMigration/ui/setIsLoading', true);
            State.commit('swagMigration/process/setIsMigrating', true);

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    State.commit('swagMigration/ui/setIsLoading', false);
                    this.isTakeoverForbidden = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (this.isTakeoverForbidden) {
                return;
            }

            await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                if (runState.isMigrationRunning === false) {
                    State.commit('swagMigration/process/setIsMigrating', false);
                    State.commit('swagMigration/ui/setIsLoading', false);
                    this.isOtherMigrationRunning = false;
                    this.$router.push({ name: 'swag.migration.index.main' });
                    return;
                }

                this.migrationService.abortMigration(runState.runUuid).then(() => {
                    State.commit('swagMigration/process/setIsMigrating', false);
                    State.commit('swagMigration/ui/setIsLoading', false);
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
            return this.migrationService.abortMigration(this.runId).then(() => {
                this.showAbortMigrationConfirmDialog = false;
                this.isMigrationInterrupted = false;
                State.commit('swagMigration/process/setIsMigrating', false);
                State.commit('swagMigration/ui/setIsPaused', false);
                State.commit('swagMigration/ui/setIsLoading', false);
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
            State.commit('swagMigration/process/setIsMigrating', false);
            State.commit('swagMigration/ui/setIsPaused', true);
            State.commit('swagMigration/ui/setIsLoading', false);
            this.isOtherMigrationRunning = false;
            State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.PAUSE_SCREEN);
        },

        onConnectionLost() {
            State.commit('swagMigration/process/setIsMigrating', false);
            State.commit('swagMigration/ui/setIsPaused', false);
            State.commit('swagMigration/ui/setDataSelectionIds', []);
            State.commit('swagMigration/ui/setDataSelectionTableData', []);
            State.commit('swagMigration/ui/setIsLoading', false);
            State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.CONNECTION_LOST);
        },

        /**
         * If the current accessToken is invalid and a migration is running
         */
        onInvalidMigrationAccessToken() {
            this.displayFlowChart = false;
            this.isMigrationInterrupted = false;
            State.commit('swagMigration/process/setIsMigrating', false);
            State.commit('swagMigration/ui/setIsPaused', false);
            this.isOtherMigrationRunning = true;
            State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.TAKEOVER);
            State.commit('swagMigration/ui/setIsLoading', false);
        },

        onWarningConfirmationChanged(confirmed) {
            this.isWarningConfirmed = confirmed;
        },

        onProcessMediaFiles() {
            State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.MEDIA_SCREEN);
        },
    },
});
