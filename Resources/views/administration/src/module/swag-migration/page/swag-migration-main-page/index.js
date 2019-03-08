import { Component, State } from 'src/core/shopware';
import template from './swag-migration-main-page.html.twig';
import { MIGRATION_STATUS } from '../../../../core/service/migration/swag-migration-worker-status-manager.service';
import {
    WORKER_INTERRUPT_TYPE,
    MIGRATION_ACCESS_TOKEN_NAME
} from '../../../../core/service/migration/swag-migration-worker.service';
import { UI_COMPONENT_INDEX } from '../../../../core/data/MigrationUIStore';
import './swag-migration-main-page.scss';

Component.register('swag-migration-main-page', {
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
            connectionEstablished: false,
            errorList: [],
            isOtherInstanceFetching: false,
            isMigrationInterrupted: false,
            isOtherMigrationRunning: false,
            showAbortMigrationConfirmDialog: false,
            isPausedBeforeAbortDialog: false,
            /** @type ApiService */
            migrationGeneralSettingStore: State.getStore('swag_migration_general_setting'),
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess'),
            /** @type MigrationUIStore */
            migrationUIStore: State.getStore('migrationUI'),
            UI_COMPONENT_INDEX: UI_COMPONENT_INDEX
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

        isUpdateAvailable() {
            return (
                this.migrationProcessStore.state.environmentInformation.updateAvailable !== null
                && this.migrationProcessStore.state.environmentInformation.updateAvailable === true
            );
        }
    },

    watch: {
        'migrationProcessStore.state.statusIndex': {
            immediate: true,
            /**
             * @param {number} status
             */
            handler(status) {
                if (status === MIGRATION_STATUS.FINISHED) {
                    this.isOtherMigrationRunning = false;
                    if (this.migrationProcessStore.state.errors.length > 0) {
                        this.onFinishWithErrors(this.migrationProcessStore.state.errors);
                    } else {
                        this.onFinishWithoutErrors();
                    }
                }
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

            if (this.migrationProcessStore.state.isMigrating === false) {
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

                        this.migrationUIStore.setIsPaused(runState.isMigrationRunning);
                        if (this.migrationUIStore.state.isPaused) {
                            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.PAUSE_SCREEN);
                        }
                    });
                }
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
                        this.migrationProcessStore.state.isMigrating ||
                        this.migrationWorkerService.status === MIGRATION_STATUS.FINISHED
                    ) {
                        this.restoreRunningMigration();
                    }

                    if (this.$route.params.startMigration) {
                        this.onMigrate();
                    }

                    this.connectionEstablished = (connectionCheckResponse.errorCode === -1);
                    this.migrationUIStore.setIsLoading(false);
                }).catch(() => {
                    this.connectionEstablished = false;
                    this.migrationUIStore.setIsLoading(false);
                });

            window.addEventListener('beforeunload', this.onBrowserTabClosing.bind(this));
        },

        restoreRunningMigration() {
            if (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.FINISHED) {
                return;
            }

            // show loading or premapping screen
            if (this.migrationProcessStore.state.statusIndex === MIGRATION_STATUS.PREMAPPING) {
                this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.PREMAPPING);
            } else {
                this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.LOADING_SCREEN);
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
            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.DATA_SELECTOR);
            this.migrationProcessStore.setIsMigrating(false);
            this.isOtherMigrationRunning = false;
        },

        onStartButtonClick() {
            this.migrationUIStore.setIsLoading(true);
            this.migrationService.writePremapping(
                this.migrationProcessStore.state.runId,
                this.migrationUIStore.state.premapping
            ).then(() => {
                this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.LOADING_SCREEN);
                this.migrationUIStore.setIsLoading(false);
                this.migrationWorkerService.startMigration(
                    this.migrationProcessStore.state.runId
                ).catch(() => {
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
                    this.isOtherInstanceFetching = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (!this.isOtherInstanceFetching) {
                await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                    this.migrationUIStore.setIsLoading(false);
                    this.migrationUIStore.setIsPaused(false);

                    if (runState.isMigrationAccessTokenValid === false) {
                        this.onInterrupt(WORKER_INTERRUPT_TYPE.TAKEOVER);
                        this.isOtherInstanceFetching = (runState.status === MIGRATION_STATUS.FETCH_DATA);
                        return;
                    }

                    if (runState.isMigrationRunning === false) {
                        this.migrationProcessStore.setIsMigrating(false);
                        this.isOtherInstanceFetching = (runState.status === MIGRATION_STATUS.FETCH_DATA);
                        this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.DATA_SELECTOR);
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
                    this.isOtherInstanceFetching = true;
                    this.onInvalidMigrationAccessToken();
                    return;
                }

                this.migrationWorkerService.subscribeInterrupt(this.onInterrupt.bind(this));

                await this.migrationWorkerService.createMigration(
                    this.migrationUIStore.state.dataSelectionIds
                ).then((runState) => {
                    this.migrationProcessStore.setEntityGroups(runState.runProgress);
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

                    localStorage.setItem(MIGRATION_ACCESS_TOKEN_NAME, runState.accessToken);
                    this.migrationProcessStore.setRunId(runState.runUuid);
                    this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.PREMAPPING);
                    this.migrationUIStore.setIsLoading(false);
                });
            });
        },

        onFinishWithoutErrors() {
            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.RESULT_SUCCESS);
        },

        onFinishWithErrors(errors) {
            errors.forEach((error) => {
                const snippetName = `swag-migration.index.error.${error.code}`;
                this.errorList.push(Object.assign(error, { snippet: { snippetName: snippetName, details: error.details } }));
            });

            this.errorList = this.errorList.map((item) => item.snippet);
            this.errorList = [...new Set(this.errorList)];

            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.RESULT_WARNING);
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
            if (this.migrationProcessStore.state.isMigrating) {
                const dialogText = this.$tc('swag-migration.index.browserClosingHint');
                e.returnValue = dialogText;
                return dialogText;
            }

            return '';
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
                    this.isOtherInstanceFetching = true;
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (!this.isOtherInstanceFetching) {
                await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                    if (runState.isMigrationRunning === false) {
                        this.migrationProcessStore.setIsMigrating(false);
                        this.migrationUIStore.setIsLoading(false);
                        this.isOtherMigrationRunning = false;
                        this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.DATA_SELECTOR);
                        return;
                    }

                    this.migrationWorkerService.takeoverMigration().then(() => {
                        this.migrationUIStore.setIsLoading(false);
                        this.migrationWorkerService.restoreRunningMigration();
                        this.restoreRunningMigration();
                    });
                });
            }
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
            this.swagMigrationRunService.updateById(this.migrationProcessStore.state.runId, { status: 'aborted' });

            this.showAbortMigrationConfirmDialog = false;
            this.isMigrationInterrupted = false;
            this.migrationProcessStore.setIsMigrating(false);
            this.migrationUIStore.setIsPaused(false);
            this.migrationUIStore.setIsLoading(false);
            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.DATA_SELECTOR);
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

        /**
         * If the current accessToken is invalid and a migration is running
         */
        onInvalidMigrationAccessToken() {
            this.isMigrationInterrupted = false;
            this.migrationProcessStore.setIsMigrating(false);
            this.migrationUIStore.setIsPaused(false);
            this.isOtherMigrationRunning = true;
            this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.TAKEOVER);
        }
    }
});
