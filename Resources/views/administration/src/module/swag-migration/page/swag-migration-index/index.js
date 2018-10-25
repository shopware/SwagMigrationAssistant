import { Component, State } from 'src/core/shopware';
import template from './swag-migration-index.html.twig';
import './swag-migration-index.less';

Component.register('swag-migration-index', {
    template,

    inject: ['migrationService', 'migrationWorkerService'],

    data() {
        return {
            isLoading: true,
            profile: {},
            environmentInformation: {},
            entityCounts: {},
            componentIndex: 0,
            components: {
                dataSelector: 0,
                loadingScreen: 1,
                resultSuccess: 2,
                resultWarning: 3,
                resultFailure: 4
            },
            errorList: [],
            statusIndex: 0,
            isMigrating: false,
            isMigrationAllowed: false,
            showConfirmDialog: false,
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
        shopDomain() {
            return this.environmentInformation.sourceSystemDomain;
        },

        shopVersion() {
            return this.environmentInformation.sourceSystemVersion;
        },

        shopFirstLetter() {
            return this.environmentInformation.sourceSystemName[0];
        },

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
        }
    },

    created() {
        this.createdComponent();
    },

    beforeDestroy() {
        this.migrationWorkerService.unsubscribeProgress();
        this.migrationWorkerService.unsubscribeStatus();
        this.migrationWorkerService.unsubscribeUpdateEntityCount();
    },

    methods: {
        createdComponent() {
            if (
                this.migrationWorkerService.isMigrating ||
                this.migrationWorkerService.status === this.migrationWorkerService.MIGRATION_STATUS.FINISHED
            ) {
                this.restoreRunningMigration();
            }

            const params = {
                limit: 1,
                term: { gateway: 'api' }
            };

            // Get profile with credentials from server
            this.migrationProfileStore.getList(params).then((response) => {
                if (!response) {
                    return;
                }

                if (response.items.length === 0) {
                    return;
                }

                this.profile = response.items[0];

                // check if credentials are given
                if (
                    !this.profile.credentialFields.endpoint ||
                    !this.profile.credentialFields.apiUser ||
                    !this.profile.credentialFields.apiKey
                ) {
                    this.$router.push({ name: 'swag.migration.wizard.introduction' });
                    return;
                }

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

            // Get possible targets
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

            window.addEventListener('beforeunload', this.onBrowserTabClosing.bind(this));
        },

        restoreRunningMigration() {
            this.isMigrating = true;

            // show loading screen
            this.componentIndex = this.components.loadingScreen;

            // Get current status
            this.onStatus({ status: this.migrationWorkerService.status });
            if (this.migrationWorkerService.status === this.migrationWorkerService.MIGRATION_STATUS.FINISHED) {
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
                    if (this.statusIndex !== this.migrationWorkerService.MIGRATION_STATUS.DOWNLOAD_DATA) {
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

            // subscribe to the update entity count event again
            this.migrationWorkerService.subscribeUpdateEntityCount(this.onUpdateEntityCount);
        },

        showMigrateConfirmDialog() {
            this.showConfirmDialog = true;
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
            this.tableData.forEach((data) => {
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

        onMigrate() {
            this.showConfirmDialog = false;
            this.isMigrating = true;
            this.checkSelectedData();
            this.statusIndex = 0;
            this.errorList = [];
            this.resetProgress();

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

            const migrationRun = this.migrationRunStore.create();
            migrationRun.profile = this.profile.profile;
            migrationRun.totals = {
                toBeFetched: toBeFetched
            };
            migrationRun.save();

            this.migrationWorkerService.startMigration(
                migrationRun.id,
                this.profile,
                entityGroups,
                this.onStatus.bind(this),
                this.onProgress.bind(this),
                this.onUpdateEntityCount.bind(this)
            ).catch(() => {
                // show data selection again
                this.isMigrating = false;
                this.componentIndex = this.components.dataSelector;
                console.log(this.$tc('swag-migration.index.migrationAlreadyRunning')); // TODO: Replace - Design?
            });
        },

        onStatus(statusData) {
            this.resetProgress();
            this.statusIndex = statusData.status;

            if (this.statusIndex === this.migrationWorkerService.MIGRATION_STATUS.DOWNLOAD_DATA) {
                this.tableData.forEach((data) => {
                    data.selected = (data.id === 'media');
                });
            } else if (this.statusIndex === this.migrationWorkerService.MIGRATION_STATUS.FINISHED) {
                if (this.migrationWorkerService.errors.length > 0) {
                    this.onFinishWithErrors(this.migrationWorkerService.errors);
                } else {
                    this.onFinishWithoutErrors();
                }
            }
        },

        onFinishWithoutErrors() {
            this.componentIndex = this.components.resultSuccess; // show result success screen
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
            this.errorList.forEach((error) => {
                console.log(error);
            });

            this.errorList = this.errorList.map((item) => item.information);
            this.errorList = [...new Set(this.errorList)];

            this.componentIndex = this.components.resultWarning; // show result warning screen
        },

        onProgress(progressData) {
            const resultData = this.tableData.find((data) => {
                return data.entityNames.includes(progressData.entityName);
            });

            if (this.statusIndex === this.migrationWorkerService.MIGRATION_STATUS.DOWNLOAD_DATA &&
                resultData.progressBar.maxValue !== progressData.entityCount
            ) {
                resultData.progressBar.maxValue = progressData.entityCount;
            }

            resultData.progressBar.value = progressData.entityGroupProgressValue;
        },

        onUpdateEntityCount(updateData) {
            this.tableData.forEach((tableItem) => {
                updateData.forEach((updateItem) => {
                    if (tableItem.id === updateItem.id) {
                        tableItem.progressBar.maxValue = updateItem.count;
                    }
                });
            });
        },

        resetProgress() {
            this.tableData.forEach((data) => {
                data.progressBar.value = 0;
            });
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

        onCloseConfirmDialog() {
            this.showConfirmDialog = false;
        },

        onClickBack() {
            this.migrationWorkerService.status = this.migrationWorkerService.MIGRATION_STATUS.WAITING;
            this.componentIndex = this.components.dataSelector;
            this.isMigrating = false;
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
        }
    }
});
