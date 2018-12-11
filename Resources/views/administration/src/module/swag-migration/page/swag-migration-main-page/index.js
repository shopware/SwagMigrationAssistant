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
        async createdComponent() {
            this.updateLastMigrationDate();

            if (this.migrationWorkerService.isMigrating === false) {
                await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                    if (runState.isMigrationAccessTokenValid === false && runState.isMigrationRunning === true) {
                        this.onInvalidMigrationAccessToken();
                        this.isOtherInstanceFetching = runState.status === MIGRATION_STATUS.FETCH_DATA;
                        return;
                    }

                    this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                        if (isRunning) {
                            this.isOtherInstanceFetching = true;
                            this.onInvalidMigrationAccessToken();
                            return;
                        }

                        this.isPaused = runState.isMigrationRunning;
                        if (this.isPaused) {
                            this.componentIndex = this.components.pauseScreen;
                        }
                    });
                });
            }


            if (
                this.migrationWorkerService.isMigrating ||
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
                if (!response) {
                    this.$router.push({ name: 'swag.migration.wizard.select_profile' });
                    return;
                }

                if (response.items.length === 0) {
                    this.$router.push({ name: 'swag.migration.wizard.select_profile' });
                    return;
                }

                this.profile = response.items[0];

                // Do connection check
                this.migrationService.checkConnection(this.profile.id).then((connectionCheckResponse) => {
                    if (!connectionCheckResponse || connectionCheckResponse.errorCode !== -1) {
                        this.$router.push({ name: 'swag.migration.wizard.credentials' });
                        return;
                    }

                    this.environmentInformation = connectionCheckResponse;
                    this.normalizeEnvironmentInformation();
                    this.calculateProgressMaxValues();

                    this.isLoading = false;
                }).catch(() => {
                    this.$router.push({ name: 'swag.migration.wizard.credentials' });
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
            this.isMigrating = true;

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
                    if (this.statusIndex !== MIGRATION_STATUS.DOWNLOAD_DATA) {
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
            this.isMigrating = false;
            this.isOtherMigrationRunning = false;
        },

        onMigrateButtonClick() {
            this.showMigrationConfirmDialog = true;
        },

        onPauseButtonClick() {
            this.migrationWorkerService.stopMigration();
            this.isMigrating = false;
            this.isPaused = true;
            this.componentIndex = this.components.pauseScreen;
        },

        onContinueButtonClick() {
            this.isLoading = true;
            this.isOtherMigrationRunning = false;
            this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                this.isLoading = false;
                this.isPaused = false;

                if (runState.isMigrationAccessTokenValid === false) {
                    this.onInterrupt();
                    this.isOtherInstanceFetching = (runState.status === MIGRATION_STATUS.FETCH_DATA);
                    return;
                }

                if (runState.isMigrationRunning === false) {
                    this.isMigrating = false;
                    this.isOtherInstanceFetching = runState.status === MIGRATION_STATUS.FETCH_DATA;
                    this.componentIndex = this.components.dataSelector;
                    return;
                }

                this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                    if (isRunning) {
                        this.isOtherInstanceFetching = true;
                        this.onInvalidMigrationAccessToken();
                        return;
                    }

                    this.migrationWorkerService.restoreRunningMigration();
                    this.restoreRunningMigration();
                });
            });
        },

        onMigrationAbort() {
            this.showAbortMigrationConfirmDialog = false;

            if (this.isMigrating) {
                this.migrationWorkerService.stopMigration();
            }

            this.isPaused = false;
            this.isMigrating = false;
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

        async onMigrate() {
            this.isOtherMigrationRunning = false;
            this.showMigrationConfirmDialog = false;
            this.isMigrating = true;
            this.checkSelectedData();
            this.statusIndex = 0;
            this.errorList = [];

            // show loading screen
            this.componentIndex = this.components.loadingScreen;

            // get all entities in order
            const entityGroups = this.getEntityGroups();

            const toBeFetched = {};
            entityGroups.forEach((entityGroup) => {
                entityGroup.entities.forEach((entity) => {
                    toBeFetched[entity.entityName] = entity.entityCount;
                });
            });

            await this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                this.isOtherInstanceFetching = runState.status === MIGRATION_STATUS.FETCH_DATA;

                if (runState.isMigrationAccessTokenValid === false || runState.isMigrationRunning === true) {
                    this.onInvalidMigrationAccessToken();
                }
            });

            if (this.isMigrating === false) {
                return;
            }

            let isMigrationRunningInOtherTab = false;
            await this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                isMigrationRunningInOtherTab = isRunning;
            });

            if (isMigrationRunningInOtherTab) {
                this.isOtherInstanceFetching = true;
                this.onInvalidMigrationAccessToken();
                return;
            }

            this.migrationService.startMigration(this.profile.id).then((runData) => {
                localStorage.setItem(MIGRATION_ACCESS_TOKEN_NAME, runData.accessToken);
                this.swagMigrationRunService.updateById(runData.runUuid, {
                    totals: {
                        toBeFetched: toBeFetched
                    },
                    additionalData: {
                        entityGroups
                    }
                });

                this.migrationWorkerService.subscribeStatus(this.onStatus.bind(this));
                this.migrationWorkerService.subscribeProgress(this.onProgress.bind(this));
                this.migrationWorkerService.subscribeInterrupt(this.onInterrupt.bind(this));
                this.migrationWorkerService.startMigration(
                    runData.runUuid,
                    this.profile,
                    entityGroups
                ).catch(() => {
                    this.onInvalidMigrationAccessToken();
                });
            });
        },

        onStatus(statusData) {
            this.statusIndex = statusData.status;

            if (this.statusIndex === MIGRATION_STATUS.DOWNLOAD_DATA) {
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
                let snippet = null;

                if (error.information === undefined) {
                    let firstParamStart = error.detail.indexOf('"') + 1;
                    const firstParamStop = error.detail.indexOf('"', firstParamStart);
                    let argument = error.detail.substring(firstParamStart, firstParamStop);

                    const secondParamStart = error.detail.indexOf('"', firstParamStop + 1) + 1;
                    const secondParamStop = error.detail.indexOf('"', secondParamStart);

                    switch (error.code) {
                    // Authorization Error
                    case '401':
                        snippet = this.$t('swag-migration.index.error.authorizationError.information');
                        break;

                    case '408':
                        snippet = this.$t(
                            'swag-migration.index.error.canNotDownloadAsset.information',
                            { path: error.details.uri }
                        );
                        break;

                        // GatewayNotFoundException
                    case 'SWAG-MIGRATION-GATEWAY-NOT-FOUND':
                        snippet = this.$t('swag-migration.index.error.gatewayNotFound.information', {
                            notFoundGateway: argument
                        });
                        break;

                        // GatewayReadException
                    case 'SWAG-MIGRATION-GATEWAY-READ':
                        snippet = this.$t('swag-migration.index.error.gatewayRead.information', {
                            unreadableGateway: argument
                        });
                        break;

                        // LocaleNotFoundException
                    case 'SWAG-MIGRATION-LOCALE-NOT-FOUND':
                        snippet = this.$t('swag-migration.index.error.localeNotFound.information', {
                            notFoundCode: argument
                        });
                        break;

                        // NoFileSystemPermissionsException
                    case 'SWAG-MIGRATION-NO-FILE-SYSTEM-PERMISSIONS':
                        snippet = this.$t('swag-migration.index.error.noFileSystemPermissions.information');
                        break;

                        // ProfileNotFoundException
                    case 'SWAG-MIGRATION-PROFILE-NOT-FOUND':
                        snippet = this.$t('swag-migration.index.error.profileNotFound.information', {
                            notFoundProfile: argument
                        });
                        break;

                        // MigrationContextPropertyMissingException
                    case 'SWAG-MIGRATION-CONTEXT-PROPERTY-MISSING':
                        snippet = this.$t('swag-migration.index.error.migrationContextPropertyMissing.information', {
                            notFoundProperty: argument
                        });
                        break;

                        // MigrationWorkloadPropertyMissingException
                    case 'SWAG-MIGRATION-WORKLOAD-PROPERTY-MISSING':
                        snippet = this.$t('swag-migration.index.error.migrationsWorkloadPropertyMissing.information', {
                            notFoundProperty: argument
                        });
                        break;

                        // WriterNotFoundException
                    case 'SWAG-MIGRATION-WRITER-NOT-FOUND':
                        snippet = this.$t('swag-migration.index.error.writerNotFound.information', {
                            notFoundWriter: argument
                        });
                        break;

                        /* Shopware55 profile */
                        // ParentEntityForChildNotFoundException
                    case 'SWAG-MIGRATION-SHOPWARE55-PARENT-ENTITY-NOT-FOUND':
                        snippet = this.$t('swag-migration.index.error.parentEntityNotFound.information', {
                            entity: argument
                        });
                        break;

                        // AssociationEntityRequiredMissingException
                    case 'SWAG-MIGRATION-SHOPWARE55-ASSOCIATION-REQUIRED-MISSING':
                        snippet = this.$t('swag-migration.index.error.associationRequiredMissing.information', {
                            missingEntity: argument,
                            requiredFor: error.detail.substring(secondParamStart, secondParamStop)
                        });
                        break;

                        // CustomerExistsException
                    case 'SWAG-MIGRATION-SHOPWARE55-CUSTOMER-EXISTS':
                        snippet = this.$t('swag-migration.index.error.customerExists.information', {
                            mail: argument
                        });
                        break;

                    case 'SWAG-MIGRATION-SHOPWARE55-EMPTY-NECESSARY-DATA-FIELDS':
                        snippet = this.$t('swag-migration.index.error.emptyNecessaryDataFields.information', {
                            entity: error.details.entity,
                            fields: error.details.fields.join()
                        });
                        break;

                    case 'SWAG-MIGRATION-SHOPWARE55-NO-DEFAULT-SHIPPING-ADDRESS':
                        snippet = this.$t('swag-migration.index.error.noDefaultShippingAddress.information');
                        break;

                    case 'SWAG-MIGRATION-SHOPWARE55-NO-DEFAULT-BILLING-ADDRESS':
                        snippet = this.$t('swag-migration.index.error.noDefaultBillingAddress.information');
                        break;

                    case 'SWAG-MIGRATION-SHOPWARE55-NO-DEFAULT-BILLING-AND-SHIPPING-ADDRESS':
                        snippet = this.$t('swag-migration.index.error.noDefaultBillingAndShippingAddress.information');
                        break;

                    case 'SWAG-MIGRATION-SHOPWARE55-PRODUCT-MEDIA-NOT-CONVERTED':
                        snippet = this.$t('swag-migration.index.error.productMediaNotConverted.information');
                        break;

                    case 'SWAG-MIGRATION-SHOPWARE55-EMPTY-LOCALE':
                        snippet = this.$t('swag-migration.index.error.emptyLocale.information');
                        break;

                    case 'SWAG-MIGRATION-SHOPWARE55-NO-ADDRESS-DATA':
                        snippet = this.$t('swag-migration.index.error.noAddressData.information');
                        break;

                    case 'SWAG-MIGRATION-SHOPWARE55-NOT-CONVERT-ABLE-OBJECT-TYPE':
                        snippet = this.$t('swag-migration.index.error.notConvertAbleObjectType.information', {
                            objectType: error.details.objecttype
                        });
                        break;

                    case 'SWAG-MIGRATION-SHOPWARE55-INVALID-UNSERIALIZED-DATA':
                        snippet = this.$t('swag-migration.index.error.invalidUnserializedData.information', {
                            entity: error.details.entity
                        });
                        break;

                    default:
                        if (error.detail.startsWith('Notice: Undefined index:')) {
                            // Undefined Index Error
                            firstParamStart = error.detail.lastIndexOf(':') + 2;
                            argument = error.detail.substring(firstParamStart);
                            snippet = this.$t('swag-migration.index.error.undefinedIndex.information', {
                                unidentifiedIndex: argument
                            });
                        } else {
                            // Error Fallback
                            snippet = this.$t('swag-migration.index.error.unknownError.information');
                        }
                        break;
                    }

                    this.errorList.push(Object.assign(error, { information: snippet }));
                } else {
                    this.errorList.push(error);
                }
            });

            this.errorList.sort((a, b) => { return a.detail.toLowerCase() > b.detail.toLowerCase(); });
            this.errorList = this.errorList.map((item) => item.information);
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
        onTakeoverMigration() {
            this.isLoading = true;
            this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                if (runState.isMigrationRunning === false) {
                    this.isMigrating = false;
                    this.isLoading = false;
                    this.isOtherMigrationRunning = false;
                    this.componentIndex = this.components.dataSelector;
                    return;
                }

                this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                    if (isRunning) {
                        this.isLoading = false;
                        this.isOtherInstanceFetching = true;
                        this.onInvalidMigrationAccessToken();
                        return;
                    }

                    this.migrationWorkerService.takeoverMigration().then(() => {
                        this.isLoading = false;
                        this.migrationWorkerService.restoreRunningMigration();
                        this.restoreRunningMigration();
                    });
                });
            });
        },

        /**
         * If the current migration was interrupted through a takeover
         */
        onInterrupt() {
            this.componentIndex = this.components.takeover;
            this.isMigrationInterrupted = true;
            this.isMigrating = false;
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
            this.isMigrating = false;
            this.isPaused = false;
            this.isMigrationAllowed = false;
            this.isOtherMigrationRunning = true;
        }
    }
});
