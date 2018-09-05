import { Component, State } from 'src/core/shopware';
import template from './swag-migration-index.html.twig';
import './swag-migration-index.less';

Component.register('swag-migration-index', {
    template,

    inject: ['migrationProfileService', 'migrationService', 'catalogService', 'migrationWorkerService'],

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
            statusIndex: 0,
            isMigrating: false,
            isMigrationAllowed: false,
            showConfirmDialog: false,
            targets: [], // possible data target locations
            tableData: [
                {
                    id: 'customers_orders',
                    entityNames: ['customer', 'order'],
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.customersAndOrders'),
                    targetDisabled: true,
                    targetHidden: true,
                    selected: false,
                    targetId: '',
                    progressBar: {
                        value: 0,
                        maxValue: 100
                    }
                },
                {
                    id: 'categories_products',
                    // TODO revert, when the core could handle translations correctly
                    entityNames: ['category', 'product'], // 'translation'],
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.categoriesAndProducts'),
                    targetDisabled: false,
                    targetHidden: false,
                    selected: false,
                    targetId: '',
                    progressBar: {
                        value: 0,
                        maxValue: 100
                    }
                },
                {
                    id: 'media',
                    entityNames: ['media'],
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.media'),
                    targetDisabled: false,
                    targetHidden: false,
                    selected: false,
                    targetId: '',
                    progressBar: {
                        value: 0,
                        maxValue: 100
                    }
                }
            ]
        };
    },

    computed: {
        shopDomain() {
            if (this.environmentInformation.structure) {
                return this.environmentInformation.structure[0].host;
            }

            return '';
        },

        shopVersion() {
            if (
                this.environmentInformation.shopwareVersion &&
                this.environmentInformation.shopwareVersion !== '___VERSION___'
            ) {
                return this.environmentInformation.shopwareVersion;
            }

            return this.$tc('swag-migration.index.shopVersionFallback');
        },

        shopFirstLetter() {
            return this.shopVersion[0];
        }
    },

    created() {
        if (
            this.migrationWorkerService.isMigrating ||
            this.migrationWorkerService.status === this.migrationWorkerService.MIGRATION_STATUS.FINISHED
        ) {
            this.restoreRunningMigration();
        }

        const params = {
            offset: 0,
            limit: 100,
            term: { gateway: 'api' }
        };

        // Get profile with credentials from server
        this.migrationProfileService.getList(params).then((response) => {
            if (!response) {
                return;
            }

            if (response.data.length === 0) {
                return;
            }

            this.profile = response.data[0];

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
                if (!connectionCheckResponse) {
                    return;
                }

                if (!connectionCheckResponse.environmentInformation) {
                    this.$router.push({ name: 'swag.migration.wizard.credentials' });
                }

                this.environmentInformation = connectionCheckResponse.environmentInformation;
                this.normalizeEnvironmentInformation();
                this.calculateProgressMaxValues();

                this.isLoading = false;
            }).catch(() => {
                this.$router.push({ name: 'swag.migration.wizard.credentials' });
            });
        });

        // Get possible targets
        this.catalogService.getList({}).then((response) => {
            if (!response) {
                return;
            }

            response.data.forEach((catalog) => {
                this.targets.push({
                    id: catalog.id,
                    name: catalog.name
                });
            });
        });

        window.addEventListener('beforeunload', this.onBrowserTabClosing.bind(this));
    },

    beforeDestroy() {
        this.migrationWorkerService.unsubscribeProgress();
        this.migrationWorkerService.unsubscribeStatus();
    },

    methods: {
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

                    // set the progress for the group
                    data.progressBar.value = group.progress;
                }
            });

            // subscribe to the progress event again
            this.migrationWorkerService.subscribeProgress(this.onProgress);

            // subscribe to the status event again
            this.migrationWorkerService.subscribeStatus(this.onStatus);
        },

        showMigrateConfirmDialog() {
            this.showConfirmDialog = true;
        },

        normalizeEnvironmentInformation() {
            this.entityCounts.customer = this.environmentInformation.customers;
            this.entityCounts.order = this.environmentInformation.orders;
            this.entityCounts.category = this.environmentInformation.categories;
            this.entityCounts.product = this.environmentInformation.products;
            this.entityCounts.media = this.environmentInformation.assets;
            this.entityCounts.translation = 10;
        },

        calculateProgressMaxValues() {
            this.tableData.forEach((data) => {
                let totalCount = 0;
                data.entityNames.forEach((currentEntityName) => {
                    totalCount += this.entityCounts[currentEntityName];
                });
                data.progressBar.maxValue = totalCount;
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
         *          targetId: "20080911ffff4fffafffffff19830531"
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
                        targetId: data.targetId
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
            this.resetProgress();

            // show loading screen
            this.componentIndex = this.components.loadingScreen;

            // get all entities in order
            const entityGroups = this.getEntityGroups();

            this.migrationWorkerService.startMigration(
                this.profile,
                entityGroups,
                this.onStatus.bind(this),
                this.onProgress.bind(this)
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
                    this.componentIndex = this.components.resultWarning; // show result warning screen
                } else {
                    this.componentIndex = this.components.resultSuccess; // show result success screen
                }
            }
        },

        onProgress(progressData) {
            const resultData = this.tableData.find((data) => {
                return data.entityNames.includes(progressData.entityName);
            });

            resultData.progressBar.value = progressData.entityGroupProgressValue;
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
            this.$router.push({ name: 'swag.migration.wizard.credentials' });
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
