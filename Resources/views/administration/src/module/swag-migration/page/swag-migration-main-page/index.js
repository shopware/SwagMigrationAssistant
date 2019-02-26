import { Component, State } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import template from './swag-migration-main-page.html.twig';
import { MIGRATION_STATUS } from '../../../../core/service/migration/swag-migration-worker-status-manager.service';
import {
    WORKER_INTERRUPT_TYPE,
    MIGRATION_ACCESS_TOKEN_NAME
} from '../../../../core/service/migration/swag-migration-worker.service';

Component.register('swag-migration-main-page', {
    template,

    inject: ['migrationService', 'migrationWorkerService', 'swagMigrationRunService'],

    data() {
        return {
            isLoading: true,
            connection: {},
            environmentInformation: {},
            connectionEstablished: false,
            lastMigrationDate: '-',
            entityCounts: {},
            componentIndex: 0,
            components: {
                dataSelector: 0,
                premapping: 1,
                loadingScreen: 2,
                resultSuccess: 3,
                resultWarning: 4,
                resultFailure: 5,
                pauseScreen: 6,
                takeover: 7
            },
            errorList: [],
            statusIndex: -1,
            isMigrating: false,
            isMigrationAllowed: false,
            isPaused: false,
            isOtherInstanceFetching: false,
            isMigrationInterrupted: false,
            isOtherMigrationRunning: false,
            showMigrationConfirmDialog: false,
            showAbortMigrationConfirmDialog: false,
            isPausedBeforeAbortDialog: false,
            tableData: [],
            entityGroups: [],
            originEntityGroups: [],
            premapping: [],
            isPremappingValid: false,
            runUuid: ''
        };
    },

    computed: {
        migrationRunStore() {
            return State.getStore('swag_migration_run');
        },

        migrationConnectionStore() {
            return State.getStore('swag_migration_connection');
        },

        migrationGeneralSettingStore() {
            return State.getStore('swag_migration_general_setting');
        },

        componentIndexIsResult() {
            return (this.componentIndex === this.components.resultSuccess ||
                this.componentIndex === this.components.resultWarning ||
                this.componentIndex === this.components.resultFailure);
        },

        /**
         * Returns the table data without datasets that don't have any entities.
         *
         * @returns {Array}
         */
        tableDataFiltered() {
            const filtered = [];
            this.tableData.forEach((group) => {
                let containtsData = false;
                group.entityNames.forEach((name) => {
                    if (this.environmentInformation.totals[name] > 0) {
                        containtsData = true;
                    }
                });

                if (containtsData) {
                    filtered.push(group);
                }
            });

            return filtered;
        },

        abortButtonVisible() {
            return this.isPaused || (
                this.isMigrating &&
                !this.isLoading &&
                !this.componentIndexIsResult
            );
        },

        backButtonVisible() {
            return this.componentIndexIsResult &&
                this.isMigrating;
        },

        migrateButtonVisible() {
            return (!this.isMigrating && !this.isPaused) ||
                (this.statusIndex === MIGRATION_STATUS.FETCH_DATA && this.isMigrating) ||
                (this.componentIndexIsResult && this.isMigrating);
        },

        migrateButtonDisabled() {
            return this.isLoading ||
                (this.statusIndex === MIGRATION_STATUS.FETCH_DATA && this.isMigrating) ||
                !this.isMigrationAllowed ||
                this.componentIndexIsResult;
        },

        startButtonVisible() {
            return (!this.isLoading && this.statusIndex === MIGRATION_STATUS.PREMAPPING && this.isMigrating);
        },

        startButtonDisabled() {
            return this.isLoading ||
                (this.statusIndex === MIGRATION_STATUS.PREMAPPING && this.isMigrating && !this.isPremappingValid);
        },

        pauseButtonVisible() {
            return this.isMigrating &&
                !this.isPaused &&
                this.statusIndex !== MIGRATION_STATUS.WAITING &&
                this.statusIndex !== MIGRATION_STATUS.FETCH_DATA &&
                this.statusIndex !== MIGRATION_STATUS.PREMAPPING &&
                !this.componentIndexIsResult;
        },

        pauseButtonDisabled() {
            return this.isLoading;
        },

        continueButtonVisible() {
            return this.isPaused;
        },

        abortMigrationBackText() {
            if (this.isPausedBeforeAbortDialog) {
                return this.$tc('swag-migration.index.confirmAbortDialog.cancelPause');
            }

            return this.$tc('swag-migration.index.confirmAbortDialog.cancelRunning');
        }
    },

    /**
     * Watch the computed properties for the action buttons and
     * emit events to the parent if they changed.
     * The parent will take care of the attributes for the buttons and call the right methods
     * on this component if they get clicked.
     */
    watch: {
        abortButtonVisible: {
            immediate: true,
            handler(newState) {
                this.$emit('buttonStateChanged', 'abortButtonVisible', newState);
            }
        },

        backButtonVisible: {
            immediate: true,
            handler(newState) {
                this.$emit('buttonStateChanged', 'backButtonVisible', newState);
            }
        },

        migrateButtonVisible: {
            immediate: true,
            handler(newState) {
                this.$emit('buttonStateChanged', 'migrateButtonVisible', newState);
            }
        },

        migrateButtonDisabled: {
            immediate: true,
            handler(newState) {
                this.$emit('buttonStateChanged', 'migrateButtonDisabled', newState);
            }
        },

        startButtonVisible: {
            immediate: true,
            handler(newState) {
                this.$emit('buttonStateChanged', 'startButtonVisible', newState);
            }
        },

        startButtonDisabled: {
            immediate: true,
            handler(newState) {
                this.$emit('buttonStateChanged', 'startButtonDisabled', newState);
            }
        },

        pauseButtonVisible: {
            immediate: true,
            handler(newState) {
                this.$emit('buttonStateChanged', 'pauseButtonVisible', newState);
            }
        },

        pauseButtonDisabled: {
            immediate: true,
            handler(newState) {
                this.$emit('buttonStateChanged', 'pauseButtonDisabled', newState);
            }
        },

        continueButtonVisible: {
            immediate: true,
            handler(newState) {
                this.$emit('buttonStateChanged', 'continueButtonVisible', newState);
            }
        }
    },

    created() {
        this.createdComponent();
    },

    beforeDestroy() {
        this.migrationWorkerService.unsubscribeProgress();
        this.migrationWorkerService.unsubscribeStatus();
        this.migrationWorkerService.unsubscribeInterrupt();
    },

    methods: {
        setIsMigrating(value) {
            this.isMigrating = value;
            this.migrationWorkerService.isMigrating = value;
        },

        async createdComponent() {
            this.updateLastMigrationDate();
            this.isMigrating = this.migrationWorkerService.isMigrating;

            if (this.isMigrating === false) {
                await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                    if (isRunning) {
                        this.isOtherInstanceFetching = true;
                        this.onInvalidMigrationAccessToken();
                    }
                });

                if (!this.isOtherInstanceFetching) {
                    await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                        if (runState.isMigrationAccessTokenValid === false && runState.isMigrationRunning === true) {
                            this.onInvalidMigrationAccessToken();
                            this.isOtherInstanceFetching = runState.status === MIGRATION_STATUS.FETCH_DATA;
                            return;
                        }

                        this.isPaused = runState.isMigrationRunning;
                        if (this.isPaused) {
                            this.componentIndex = this.components.pauseScreen;
                        }
                    });
                }
            }

            // Get selected connection
            let connectionId = null;
            await this.migrationGeneralSettingStore.getList({ limit: 1 }).then((settings) => {
                if (!settings || settings.items.length === 0) {
                    return;
                }

                connectionId = settings.items[0].selectedConnectionId;
            });

            if (connectionId === null) {
                this.$router.push({ name: 'swag.migration.wizard.introduction' });
                return;
            }

            const params = {
                limit: 1,
                criteria: CriteriaFactory.equals('id', connectionId)
            };

            // Get connection with credentials from server
            this.migrationConnectionStore.getList(params).then((response) => {
                if (!response ||
                    (response && response.items.length === 0)
                ) {
                    this.connectionEstablished = false;
                    this.isLoading = false;
                    return;
                }

                this.connection = response.items[0];

                // Do connection check
                this.migrationService.checkConnection(this.connection.id).then(async (connectionCheckResponse) => {
                    this.environmentInformation = connectionCheckResponse;
                    this.entityCounts = this.environmentInformation.totals;

                    this.migrationService.getDataSelection(this.connection.id).then((dataSelection) => {
                        this.tableData = dataSelection;

                        if (
                            this.isMigrating ||
                            this.migrationWorkerService.status === MIGRATION_STATUS.FINISHED
                        ) {
                            this.restoreRunningMigration();
                        }

                        this.connectionEstablished = (connectionCheckResponse.errorCode === -1);
                        this.isLoading = false;
                    });
                }).catch(() => {
                    this.connectionEstablished = false;
                    this.isLoading = false;
                });
            });

            window.addEventListener('beforeunload', this.onBrowserTabClosing.bind(this));
        },

        restoreRunningMigration() {
            this.setIsMigrating(true);

            // Get data to migrate (selected table data + progress)
            this.entityGroups = this.migrationWorkerService.entityGroups;
            this.originEntityGroups = this.migrationWorkerService.entityGroups;
            this.premapping = this.migrationWorkerService.premapping;

            // Get current status
            this.onStatus({ status: this.migrationWorkerService.status });

            if (this.migrationWorkerService.status === MIGRATION_STATUS.FINISHED) {
                return;
            }

            // show loading or premapping screen
            if (this.migrationWorkerService.status === MIGRATION_STATUS.PREMAPPING) {
                this.componentIndex = this.components.premapping;
            } else {
                this.componentIndex = this.components.loadingScreen;
            }

            // subscribe to the progress event again
            this.migrationWorkerService.subscribeProgress(this.onProgress);

            // subscribe to the status event again
            this.migrationWorkerService.subscribeStatus(this.onStatus);

            // subscribe to the interrupt event again
            this.migrationWorkerService.subscribeInterrupt(this.onInterrupt);
        },

        onAbortButtonClick() {
            this.isOtherMigrationRunning = false;

            if (this.statusIndex === MIGRATION_STATUS.PREMAPPING) {
                this.isLoading = true;
                this.onInterrupt(WORKER_INTERRUPT_TYPE.STOP);
                return;
            }

            if (this.statusIndex === MIGRATION_STATUS.FETCH_DATA) {
                this.isLoading = true;
                this.migrationWorkerService.stopMigration();
                return;
            }

            this.showAbortMigrationConfirmDialog = true;
            this.isPausedBeforeAbortDialog = this.isPaused;

            if (!this.isPaused) {
                this.isLoading = true;
                this.migrationWorkerService.pauseMigration();
            }
        },

        onBackButtonClick() {
            this.migrationWorkerService.status = MIGRATION_STATUS.WAITING;
            this.componentIndex = this.components.dataSelector;
            this.setIsMigrating(false);
            this.isOtherMigrationRunning = false;
        },

        onMigrateButtonClick() {
            this.showMigrationConfirmDialog = true;
        },

        onStartButtonClick() {
            this.isLoading = true;
            this.migrationService.writePremapping(this.runUuid, this.premapping).then(() => {
                this.componentIndex = this.components.loadingScreen;
                this.isLoading = false;
                this.migrationWorkerService.startMigration(
                    this.runUuid,
                    this.entityGroups
                ).catch(() => {
                    this.onInvalidMigrationAccessToken();
                });
            });
        },

        onPauseButtonClick() {
            this.migrationWorkerService.pauseMigration();
            this.setIsMigrating(false);
            this.isLoading = true;
        },

        async onContinueButtonClick() {
            this.isLoading = true;
            this.isOtherMigrationRunning = false;
            this.setIsMigrating(true);

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    this.isLoading = false;
                    this.isOtherInstanceFetching = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (!this.isOtherInstanceFetching) {
                await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                    this.isLoading = false;
                    this.isPaused = false;

                    if (runState.isMigrationAccessTokenValid === false) {
                        this.onInterrupt(WORKER_INTERRUPT_TYPE.TAKEOVER);
                        this.isOtherInstanceFetching = (runState.status === MIGRATION_STATUS.FETCH_DATA);
                        return;
                    }

                    if (runState.isMigrationRunning === false) {
                        this.setIsMigrating(false);
                        this.isOtherInstanceFetching = runState.status === MIGRATION_STATUS.FETCH_DATA;
                        this.componentIndex = this.components.dataSelector;
                        return;
                    }

                    this.migrationWorkerService.restoreRunningMigration();
                    this.restoreRunningMigration();
                });
            }
        },

        getDataSelectionIds() {
            return Object.keys(this.$refs.dataSelector.getSelectedData());
        },

        resetProgress() {
            this.entityGroups.forEach((data) => {
                data.currentCount = 0;
            });
        },

        async onMigrate() {
            this.isOtherMigrationRunning = false;
            this.showMigrationConfirmDialog = false;

            this.$nextTick().then(async () => {
                const dataSelectionIds = this.getDataSelectionIds();
                this.setIsMigrating(true);
                this.errorList = [];

                // show loading screen
                this.resetProgress();
                this.isLoading = true;

                let isMigrationRunningInOtherTab = false;
                await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                    isMigrationRunningInOtherTab = isRunning;
                });

                if (isMigrationRunningInOtherTab) {
                    this.isLoading = false;
                    this.isOtherInstanceFetching = true;
                    this.onInvalidMigrationAccessToken();
                    return;
                }

                this.migrationWorkerService.subscribeStatus(this.onStatus.bind(this));
                this.migrationWorkerService.subscribeProgress(this.onProgress.bind(this));
                this.migrationWorkerService.subscribeInterrupt(this.onInterrupt.bind(this));

                await this.migrationWorkerService.createMigration(
                    this.connection.id,
                    dataSelectionIds
                ).then((runState) => {
                    this.entityGroups = runState.runProgress;
                    this.originEntityGroups = runState.runProgress;
                    this.isOtherInstanceFetching = runState.status === MIGRATION_STATUS.FETCH_DATA;

                    if (
                        runState.isMigrationAccessTokenValid === false ||
                        runState.isMigrationRunning === true ||
                        runState.runUuid === null ||
                        runState.accessToken === null
                    ) {
                        this.onInvalidMigrationAccessToken();
                        return;
                    }

                    this.migrationService.generatePremapping(runState.runUuid).then((premapping) => {
                        this.isLoading = false;
                        localStorage.setItem(MIGRATION_ACCESS_TOKEN_NAME, runState.accessToken);

                        if (premapping.length === 0) {
                            this.componentIndex = this.components.loadingScreen;
                            this.migrationWorkerService.startMigration(
                                runState.runUuid,
                                this.entityGroups
                            ).catch(() => {
                                this.onInvalidMigrationAccessToken();
                            });
                        } else {
                            this.runUuid = runState.runUuid;
                            this.componentIndex = this.components.premapping;
                            this.premapping = premapping;
                            this.migrationWorkerService.premapping = premapping;
                        }
                    });
                });
            });
        },

        onStatus(statusData) {
            this.statusIndex = statusData.status;

            if (this.statusIndex === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                this.entityGroups = this.originEntityGroups.filter((group) => {
                    return group.id === 'processMediaFiles';
                });
            } else if (this.statusIndex === MIGRATION_STATUS.FINISHED) {
                this.isOtherMigrationRunning = false;
                this.updateLastMigrationDate();
                if (this.migrationWorkerService.errors.length > 0) {
                    this.onFinishWithErrors(this.migrationWorkerService.errors);
                } else {
                    this.onFinishWithoutErrors();
                }
            } else {
                this.entityGroups = this.originEntityGroups.filter((group) => {
                    return group.id !== 'processMediaFiles';
                });
            }
        },

        updateLastMigrationDate() {
            const params = {
                limit: 1,
                criteria: CriteriaFactory.equals('status', 'finished'),
                sortBy: 'createdAt',
                sortDirection: 'desc'
            };

            this.migrationRunStore.getList(params).then((res) => {
                if (res && res.items.length > 0) {
                    this.lastMigrationDate = res.items[0].createdAt;
                } else {
                    this.lastMigrationDate = '-';
                }
            });
        },

        onFinishWithoutErrors() {
            this.componentIndex = this.components.resultSuccess;
        },

        onFinishWithErrors(errors) {
            errors.forEach((error) => {
                const snippetName = `swag-migration.index.error.${error.code}`;
                this.errorList.push(Object.assign(error, { snippet: { snippetName: snippetName, details: error.details } }));
            });

            this.errorList = this.errorList.map((item) => item.snippet);
            this.errorList = [...new Set(this.errorList)];

            this.componentIndex = this.components.resultWarning; // show result warning screen
        },

        onProgress(progressData) {
            const resultData = this.entityGroups.find((group) => {
                for (let i = 0; i < group.entities.length; i += 1) {
                    if (group.entities[i].entityName === progressData.entityName) {
                        return true;
                    }
                }

                return false;
            });

            if (resultData === undefined) {
                return;
            }

            if (resultData.total !== progressData.groupTotal) {
                resultData.total = progressData.groupTotal;
            }

            resultData.currentCount = progressData.groupCurrentCount;
        },

        addError(error) {
            State.getStore('error').addError({
                type: 'migration-error',
                error
            });
        },

        onCloseMigrationConfirmDialog() {
            this.showMigrationConfirmDialog = false;
        },

        onCloseAbortMigrationConfirmDialog() {
            this.showAbortMigrationConfirmDialog = false;

            if (!this.isPausedBeforeAbortDialog) {
                this.$nextTick(() => {
                    this.onContinueButtonClick();
                });
            }
        },

        onBrowserTabClosing(e) {
            if (this.isMigrating) {
                const dialogText = this.$tc('swag-migration.index.browserClosingHint');
                e.returnValue = dialogText;
                return dialogText;
            }

            return '';
        },

        onMigrationAllowed(allowed) {
            this.isMigrationAllowed = allowed;
        },

        onPremappingValid(valid) {
            this.isPremappingValid = valid;
        },

        /**
         * Check if a takeover is allowed, takeover migration and restore state
         */
        async onTakeoverMigration() {
            this.setIsMigrating(true);
            this.isLoading = true;

            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    this.isLoading = false;
                    this.isOtherInstanceFetching = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (!this.isOtherInstanceFetching) {
                await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                    if (runState.isMigrationRunning === false) {
                        this.setIsMigrating(false);
                        this.isLoading = false;
                        this.isOtherMigrationRunning = false;
                        this.componentIndex = this.components.dataSelector;
                        return;
                    }

                    this.migrationWorkerService.takeoverMigration().then(() => {
                        this.isLoading = false;
                        this.migrationWorkerService.restoreRunningMigration();
                        this.restoreRunningMigration();
                    });
                });
            }
        },

        /**
         * If the current migration was interrupted through a takeover
         */
        onInterrupt(type) {
            if (type === WORKER_INTERRUPT_TYPE.TAKEOVER) {
                this.onConfiscatedMigration();
            } else if (type === WORKER_INTERRUPT_TYPE.STOP) {
                this.onStop();
            } else if (type === WORKER_INTERRUPT_TYPE.PAUSE) {
                this.onPause();
            }
        },

        /**
         * If the current migration was confiscated by a takeover from another client
         */
        onConfiscatedMigration() {
            this.onInvalidMigrationAccessToken();
            this.isMigrationInterrupted = true;
        },

        /**
         * If the current migration was stopped
         */
        onStop() {
            this.swagMigrationRunService.updateById(this.migrationWorkerService.runId, { status: 'aborted' });

            this.showAbortMigrationConfirmDialog = false;
            this.isMigrationInterrupted = false;
            this.setIsMigrating(false);
            this.isPaused = false;
            this.isMigrationAllowed = true;
            this.isLoading = false;
            this.componentIndex = this.components.dataSelector;
        },

        /**
         * If the current migration was paused
         */
        onPause() {
            this.isMigrationInterrupted = false;
            this.setIsMigrating(false);
            this.isPaused = true;
            this.isLoading = false;
            this.isMigrationAllowed = false;
            this.isOtherMigrationRunning = false;
            this.componentIndex = this.components.pauseScreen;
        },

        /**
         * If the current accessToken is invalid and a migration is running
         */
        onInvalidMigrationAccessToken() {
            this.isMigrationInterrupted = false;
            this.setIsMigrating(false);
            this.isPaused = false;
            this.isMigrationAllowed = false;
            this.isOtherMigrationRunning = true;
            this.componentIndex = this.components.takeover;
        }
    }
});
