import { Component, State } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import template from './swag-migration-main-page.html.twig';
import { MIGRATION_STATUS } from '../../../../core/service/migration/swag-migration-worker-status-manager.service';
import { MIGRATION_ACCESS_TOKEN_NAME } from '../../../../core/service/migration/swag-migration-worker.service';

Component.register('swag-migration-main-page', {
    template,

    inject: ['migrationService', 'migrationWorkerService', 'swagMigrationRunService'],

    data() {
        return {
            isLoading: true,
            profile: {},
            environmentInformation: {},
            connectionEstablished: false,
            lastMigrationDate: '-',
            entityCounts: {},
            componentIndex: 0,
            components: {
                dataSelector: 0,
                loadingScreen: 1,
                resultSuccess: 2,
                resultWarning: 3,
                resultFailure: 4,
                pauseScreen: 5,
                takeover: 6
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
            catalogs: [],
            salesChannels: [],
            tableData: [
                {
                    id: 'categories_products',
                    // TODO revert, when the core could handle translations correctly
                    entityNames: ['category', 'product'], // 'translation'],
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.categoriesAndProducts'),
                    selected: false,
                    target: 'catalog',
                    targets: [],
                    targetId: '',
                    progressBar: {
                        value: 0,
                        maxValue: 0
                    }
                },
                {
                    id: 'customers_orders',
                    entityNames: ['customer', 'order'],
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.customersAndOrders'),
                    selected: false,
                    target: 'salesChannel',
                    targets: [],
                    targetId: '',
                    progressBar: {
                        value: 0,
                        maxValue: 0
                    }
                },
                {
                    id: 'media',
                    entityNames: ['media'],
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.media'),
                    selected: false,
                    target: 'catalog',
                    targets: [],
                    targetId: '',
                    progressBar: {
                        value: 0,
                        maxValue: 0
                    }
                }
            ]
        };
    },

    computed: {
        catalogStore() {
            return State.getStore('catalog');
        },

        salesChannelStore() {
            return State.getStore('sales_channel');
        },

        migrationRunStore() {
            return State.getStore('swag_migration_run');
        },

        migrationProfileStore() {
            return State.getStore('swag_migration_profile');
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
            this.tableData.forEach((data) => {
                if (data.progressBar.maxValue > 0) {
                    filtered.push(data);
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
                (
                    this.componentIndexIsResult &&
                    this.isMigrating
                );
        },

        migrateButtonDisabled() {
            return (this.statusIndex === MIGRATION_STATUS.FETCH_DATA && this.isMigrating) ||
                !this.isMigrationAllowed ||
                this.componentIndexIsResult;
        },

        pauseButtonVisible() {
            return this.isMigrating &&
                !this.isPaused &&
                this.statusIndex !== MIGRATION_STATUS.FETCH_DATA &&
                !this.componentIndexIsResult;
        },

        continueButtonVisible() {
            return this.isPaused;
        },

        abortMigrationBackText() {
            if (this.isPaused) {
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

        pauseButtonVisible: {
            immediate: true,
            handler(newState) {
                this.$emit('buttonStateChanged', 'pauseButtonVisible', newState);
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

            if (
                this.isMigrating ||
                this.migrationWorkerService.status === MIGRATION_STATUS.FINISHED
            ) {
                this.restoreRunningMigration();
            }

            // Get selected profile id
            let profileId = null;
            await this.migrationGeneralSettingStore.getList({ limit: 1 }).then((settings) => {
                if (!settings || settings.items.length === 0) {
                    return;
                }

                profileId = settings.items[0].selectedProfileId;
            });

            if (profileId === null) {
                this.$router.push({ name: 'swag.migration.wizard.introduction' });
                return;
            }

            const params = {
                limit: 1,
                criteria: CriteriaFactory.equals('id', profileId)
            };

            // Get profile with credentials from server
            this.migrationProfileStore.getList(params).then((response) => {
                if (!response ||
                    (response && response.items.length === 0)
                ) {
                    this.connectionEstablished = false;
                    this.isLoading = false;
                    return;
                }

                this.profile = response.items[0];

                // Do connection check
                this.migrationService.checkConnection(this.profile.id).then((connectionCheckResponse) => {
                    this.environmentInformation = connectionCheckResponse;
                    this.normalizeEnvironmentInformation();
                    this.calculateProgressMaxValues();

                    this.connectionEstablished = (connectionCheckResponse.errorCode === -1);
                    this.isLoading = false;
                }).catch(() => {
                    this.connectionEstablished = false;
                    this.isLoading = false;
                });
            });

            this._getPossibleTargets();
            window.addEventListener('beforeunload', this.onBrowserTabClosing.bind(this));
        },

        _getPossibleTargets() {
            const catalogPromise = this.catalogStore.getList({});
            const salesChannelPromise = this.salesChannelStore.getList({});

            Promise.all([catalogPromise, salesChannelPromise]).then((responses) => {
                this.catalogs = responses[0].items;
                this.salesChannels = responses[1].items;

                this.tableData.forEach((tableItem) => {
                    if (tableItem.target === 'catalog') {
                        tableItem.targets = this.catalogs;
                    } else if (tableItem.target === 'salesChannel') {
                        tableItem.targets = this.salesChannels;
                    } else {
                        tableItem.targets = [];
                    }

                    if (tableItem.targets.length !== 0) {
                        tableItem.targetId = tableItem.targets[0].id;
                    }
                });
            });
        },

        restoreRunningMigration() {
            this.setIsMigrating(true);

            // show loading screen
            this.componentIndex = this.components.loadingScreen;

            // Get current status
            this.onStatus({ status: this.migrationWorkerService.status });
            if (this.migrationWorkerService.status === MIGRATION_STATUS.FINISHED) {
                return;
            }

            // Get data to migrate (selected table data + progress)
            const selectedEntityGroups = this.migrationWorkerService.entityGroups;
            this.tableData.forEach((data) => {
                const group = selectedEntityGroups.find((g) => {
                    return g.id === data.id;
                });

                if (group !== undefined) {
                    // found entity in group -> means it was selected
                    if (this.statusIndex !== MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                        data.selected = true;
                    }

                    // set the progress max value from our service
                    data.progressBar.maxValue = group.count;

                    // set the progress for the group
                    data.progressBar.value = group.progress;
                }
            });

            // subscribe to the progress event again
            this.migrationWorkerService.subscribeProgress(this.onProgress);

            // subscribe to the status event again
            this.migrationWorkerService.subscribeStatus(this.onStatus);

            // subscribe to the interrupt event again
            this.migrationWorkerService.subscribeInterrupt(this.onInterrupt);
        },

        onAbortButtonClick() {
            if (this.statusIndex === MIGRATION_STATUS.FETCH_DATA) {
                this.onMigrationAbort();
            } else {
                this.showAbortMigrationConfirmDialog = true;

                if (!this.isPaused) {
                    this.migrationWorkerService.stopMigration();
                    this.componentIndex = this.components.pauseScreen;
                }
            }
            this.isOtherMigrationRunning = false;
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

        onPauseButtonClick() {
            this.migrationWorkerService.stopMigration();
            this.setIsMigrating(false);
            this.isPaused = true;
            this.componentIndex = this.components.pauseScreen;
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
                        this.onInterrupt();
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

        onMigrationAbort() {
            this.showAbortMigrationConfirmDialog = false;

            if (this.isMigrating) {
                this.migrationWorkerService.stopMigration();
            }

            this.isPaused = false;
            this.setIsMigrating(false);
            this.componentIndex = this.components.dataSelector;
            this.swagMigrationRunService.updateById(this.migrationWorkerService.runId, { status: 'aborted' });
        },

        normalizeEnvironmentInformation() {
            this.entityCounts.customer = this.environmentInformation.customerTotal;
            this.entityCounts.order = this.environmentInformation.orderTotal;
            this.entityCounts.category = this.environmentInformation.categoryTotal;
            this.entityCounts.product = this.environmentInformation.productTotal;
            this.entityCounts.media = this.environmentInformation.assetTotal;
            this.entityCounts.translation = this.environmentInformation.translationTotal;
        },

        calculateProgressMaxValues() {
            this.tableData.forEach((data) => {
                // Skip the calculation for maxValues that we have from our service (in case of restore)
                if (data.progressBar.maxValue === 0) {
                    let totalCount = 0;
                    data.entityNames.forEach((currentEntityName) => {
                        totalCount += this.entityCounts[currentEntityName];
                    });
                    data.progressBar.maxValue = totalCount;
                }
            });
        },

        checkSelectedData() {
            const selectedObject = this.$refs.dataSelector.getSelectedData();
            this.tableData.forEach((data) => {
                data.selected = data.id in selectedObject;
            });
        },

        /**
         * Creates an data array similar to the following:
         * [
         *      {
         *          id: "customers_orders"
         *          entities: [
         *              {
         *                  entityName: "customer",
         *                  entityCount: 2
         *              },
         *              {
         *                  entityName: "order",
         *                  entityCount: 4
         *              }
         *          ],
         *          targetId: "20080911ffff4fffafffffff19830531",
         *          target: "salesChannel"
         *          count: 6
         *          progress: 6
         *      },
         *      ...
         *  ]
         *
         * @returns {Array}
         */
        getEntityGroups() {
            const entityGroups = [];
            this.tableDataFiltered.forEach((data) => {
                if (data.selected) {
                    const entities = [];
                    let groupCount = 0;
                    data.entityNames.forEach((name) => {
                        entities.push({
                            entityName: name,
                            entityCount: this.entityCounts[name]
                        });
                        groupCount += this.entityCounts[name];
                    });

                    entityGroups.push({
                        id: data.id,
                        entities: entities,
                        progress: 0,
                        count: groupCount,
                        targetId: data.targetId,
                        target: data.target
                    });
                }
            });

            return entityGroups;
        },

        resetProgress() {
            this.tableData.forEach((data) => {
                data.progressBar.value = 0;
            });
        },

        async onMigrate() {
            this.isOtherMigrationRunning = false;
            this.showMigrationConfirmDialog = false;
            this.setIsMigrating(true);
            this.checkSelectedData();
            this.statusIndex = 0;
            this.errorList = [];

            // show loading screen
            this.resetProgress();
            this.componentIndex = this.components.loadingScreen;

            // get all entities in order
            const entityGroups = this.getEntityGroups();

            const toBeFetched = {};
            entityGroups.forEach((entityGroup) => {
                entityGroup.entities.forEach((entity) => {
                    toBeFetched[entity.entityName] = entity.entityCount;
                });
            });

            let isMigrationRunningInOtherTab = false;
            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                isMigrationRunningInOtherTab = isRunning;
            });

            if (isMigrationRunningInOtherTab) {
                this.isOtherInstanceFetching = true;
                this.onInvalidMigrationAccessToken();
                return;
            }

            await this.migrationWorkerService.createNewMigration(
                this.profile.id,
                {
                    toBeFetched
                },
                {
                    entityGroups
                }
            ).then((runState) => {
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

                this.migrationWorkerService.subscribeStatus(this.onStatus.bind(this));
                this.migrationWorkerService.subscribeProgress(this.onProgress.bind(this));
                this.migrationWorkerService.subscribeInterrupt(this.onInterrupt.bind(this));
                this.migrationWorkerService.startMigration(
                    runState.runUuid,
                    this.profile,
                    entityGroups
                ).catch(() => {
                    this.onInvalidMigrationAccessToken();
                });
            });
        },

        onStatus(statusData) {
            this.statusIndex = statusData.status;

            if (this.statusIndex === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                this.tableData.forEach((data) => {
                    data.selected = (data.id === 'media');
                });
            } else if (this.statusIndex === MIGRATION_STATUS.FINISHED) {
                this.isOtherMigrationRunning = false;
                this.updateLastMigrationDate();
                if (this.migrationWorkerService.errors.length > 0) {
                    this.onFinishWithErrors(this.migrationWorkerService.errors);
                } else {
                    this.onFinishWithoutErrors();
                }
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
            const resultData = this.tableData.find((data) => {
                return data.entityNames.includes(progressData.entityName);
            });

            if (resultData.progressBar.maxValue !== progressData.entityCount) {
                resultData.progressBar.maxValue = progressData.entityCount;
            }

            resultData.progressBar.value = progressData.entityGroupProgressValue;
        },

        addError(error) {
            State.getStore('error').addError({
                type: 'migration-error',
                error
            });
        },

        editSettings() {
            this.$router.push({
                name: 'swag.migration.wizard.credentials',
                params: {
                    editMode: true
                }
            });
        },

        onCloseMigrationConfirmDialog() {
            this.showMigrationConfirmDialog = false;
        },

        onCloseAbortMigrationConfirmDialog() {
            this.showAbortMigrationConfirmDialog = false;

            if (!this.isPaused) {
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
        onInterrupt() {
            this.componentIndex = this.components.takeover;
            this.isMigrationInterrupted = true;
            this.setIsMigrating(false);
            this.isPaused = false;
            this.isMigrationAllowed = false;
            this.isOtherMigrationRunning = true;
        },

        /**
         * If the current accessToken is invalid and a migration is running
         */
        onInvalidMigrationAccessToken() {
            this.componentIndex = this.components.takeover;
            this.isMigrationInterrupted = false;
            this.setIsMigrating(false);
            this.isPaused = false;
            this.isMigrationAllowed = false;
            this.isOtherMigrationRunning = true;
        }
    }
});
