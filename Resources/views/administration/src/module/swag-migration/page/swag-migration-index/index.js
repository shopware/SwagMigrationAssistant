import {Component, State} from 'src/core/shopware';
import template from './swag-migration-index.html.twig';
import './swag-migration-index.less';

Component.register('swag-migration-index', {
    template,

    inject: ['migrationProfileService', 'migrationService', 'catalogService', 'migrationWorkerService'],

    data() {
        return {
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
            showConfirmDialog: false,
            targets: [],        //possible data target locations
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
                    entityNames: ['category', 'product'], // 'translation'], TODO revert, when the core could handle translations correctly
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.categoriesAndProducts'),
                    targetDisabled: true,
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
                    targetDisabled: true,
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
        shopFirstLetter() {
            if (this.environmentInformation.shopwareVersion)
                return this.environmentInformation.shopwareVersion[0];

            return '';
        },

        shopDomain() {
            if (this.environmentInformation.strucure)
                return this.environmentInformation.strucure[0].host;

            return '';
        },

        shopVersion() {
            if (this.environmentInformation.shopwareVersion)
                return this.environmentInformation.shopwareVersion;

            return '';
        }
    },

    created() {
        const params = {
            offset: 0,
            limit: 100,
            term: { gateway: 'api' }
        };

        //Get profile with credentials from server
        this.migrationProfileService.getList(params).then((response) => {
            this.profile = response.data[0];

            //check if credentials are given
            if (!this.profile.credentialFields.endpoint || !this.profile.credentialFields.apiUser || !this.profile.credentialFields.apiKey) {
                this.$router.push({ name: 'swag.migration.wizard.introduction' });
                return;
            }

            //Do connection check
            this.migrationService.checkConnection(this.profile.id).then((connectionCheckResponse) => {
                if (!connectionCheckResponse.success) {
                    this.$router.push({ name: 'swag.migration.wizard.credentials' });
                }

                this.environmentInformation = connectionCheckResponse.environmentInformation;
                this.normalizeEnvironmentInformation();
                this.calculateProgressMaxValues();
            }).catch((error) => {
                this.$router.push({ name: 'swag.migration.wizard.credentials' });
            });
        });

        //Get possible targets
        this.catalogService.getList({ offset: 0, limit: 100 }).then((response) => {
            response.data.forEach((catalog) => {
                this.targets.push({
                    id: catalog.id,
                    name: catalog.name
                });
            });
        });
    },

    methods: {
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
            let selectedObject = this.$refs.dataSelector.getSelectedData();
            this.tableData.forEach((data) => {
                data.selected = data.id in selectedObject;
            });
        },

        getAllEntityNames() {
            let allEntityNames = [];
            this.tableData.forEach((data) => {
                if (data.selected) {
                    allEntityNames = allEntityNames.concat(data.entityNames);
                }
            });

            return allEntityNames;
        },

        async onMigrate() {
            this.showConfirmDialog = false;
            this.isMigrating = true;
            this.checkSelectedData();
            this.statusIndex = 0;
            this.resetProgress();

            //show loading screen
            this.componentIndex = this.components.loadingScreen;

            //get all entities in order
            let allEntityNames = this.getAllEntityNames();

            //step 1 - read/fetch
            await this.migrationWorkerService.fetchData(this.profile, allEntityNames, this.entityCounts, (progressData) => {
                this.onProgress(progressData.entityName, progressData.newOffset, progressData.deltaOffset, progressData.entityCount);
            });

            //step 2- write
            this.statusIndex = 1;
            this.resetProgress();
            await this.migrationWorkerService.writeData(this.profile, allEntityNames, this.entityCounts, (progressData) => {
                this.onProgress(progressData.entityName, progressData.newOffset, progressData.deltaOffset, progressData.entityCount);
            });

            //step 3 - media download
            //TODO: implment media download
            this.statusIndex = 2;
            this.resetProgress();

            this.tableData[2].progressBar.value = 100;

            if (State.getStore('error').errors.system.length > 0) {
                this.componentIndex = this.components.resultWarning;    //show result warning screen
            } else {
                this.componentIndex = this.components.resultSuccess;    //show result success screen
            }
        },

        onProgress(entityName, newOffset, deltaOffset, entityCount) {
            let data = this.tableData.find((data) => {
                return data.entityNames.includes(entityName);
            });

            data.progressBar.value +=  deltaOffset;
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
            this.componentIndex = this.components.dataSelector;
            this.isMigrating = false;
        }
    }
});
