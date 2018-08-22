import {Component, State} from 'src/core/shopware';
import template from './swag-migration-index.html.twig';
import './swag-migration-index.less';

Component.register('swag-migration-index', {
    template,

    inject: ['migrationProfileService', 'migrationService', 'catalogService'],

    data() {
        return {
            profile: {},
            environmentInformation: {},
            componentIndex: 0,
            components: {
                dataSelector: 0,
                loadingScreen: 1,
                resultSuccess: 2,
                resultWarning: 3,
                resultFailure: 4
            },
            isMigrating: false,
            showConfirmDialog: false,
            targets: [],
            selectedData: {},
            tableData: [
                {
                    id: 'customers_orders',
                    entityNames: ['customer', 'order'],
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.customersAndOrders'),
                    targetDisabled: true,
                    targetHidden: true,
                    targetId: ''
                },
                {
                    id: 'categories_products',
                    entityNames: ['category', 'product'], // 'translation'], TODO revert, when the core could handle translations correctly
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.categoriesAndProducts'),
                    targetDisabled: true,
                    targetHidden: false,
                    targetId: ''
                },
                {
                    id: 'media',
                    entityNames: ['media'],
                    data: this.$tc('swag-migration.index.selectDataCard.dataPossible.media'),
                    targetDisabled: true,
                    targetHidden: false,
                    targetId: ''
                }
            ],
            statusIndex: 0,
            progressBars: [],
            progressBarsPossible: [
                {
                    entityNames: ['customer', 'order'],
                    parentId: 'customers_orders',
                    title: this.$tc('swag-migration.index.selectDataCard.dataPossible.customersAndOrders'),
                    value: 0
                },
                {
                    entityNames: ['category', 'product'], // 'translation'], TODO revert, when the core could handle translations correctly
                    parentId: 'categories_products',
                    title: this.$tc('swag-migration.index.selectDataCard.dataPossible.categoriesAndProducts'),
                    value: 0
                },
                {
                    entityNames: ['media'],
                    parentId: 'media',
                    title: this.$tc('swag-migration.index.selectDataCard.dataPossible.media'),
                    value: 0
                }
            ],
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
            }).catch((error) => {
                this.$router.push({ name: 'swag.migration.wizard.credentials' });
            });
        });

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

        async onMigrate() {
            this.showConfirmDialog = false;
            this.isMigrating = true;
            this.selectedData = this.$refs.dataSelector.getSelectedData();
            this.statusIndex = 0;
            this.resetProgress();

            this.componentIndex = this.components.loadingScreen;    //show loading screen

            //get all entities in order
            let allEntityNames = [];
            this.tableData.forEach((row) => {
                if (row.id in this.selectedData) {
                    row.entityNames.forEach((entityName) => {
                        allEntityNames.push(entityName);
                    });
                }
            });

            //step 1 - read/fetch
            //call the api with the entities in right order
            for (let i = 0; i < allEntityNames.length; i++) {
                await this.migrateEntityRequest(allEntityNames[i], 'fetchData');
            }

            //step 2- write
            this.statusIndex = 1;
            this.resetProgress();

            for (let i = 0; i < allEntityNames.length; i++) {
                await this.migrateEntityRequest(allEntityNames[i], 'writeData');
            }

            //step 3 - media download
            //TODO: implment media download
            this.statusIndex = 2;
            this.resetProgress();

            this.progressBars[0].value = 100;

            if (State.getStore('error').errors.system.length > 0) {
                this.componentIndex = this.components.resultWarning;    //show result warning screen
            } else {
                this.componentIndex = this.components.resultSuccess;    //show result success screen
            }
        },

        onProgress(entityName) {
            let progressBar = this.progressBars.find((bar) => {
                return bar.entityNames.includes(entityName);
            });

            let entityIndex = progressBar.entityNames.findIndex((entity) => {
                return entity === entityName;
            });

            let entityCount = progressBar.entityNames.length;

            progressBar.value = (entityIndex + 1) / entityCount * 100;
        },

        resetProgress() {
            //copy the progressBarsPossible to progressBars
            this.progressBars = [];
            this.progressBarsPossible.forEach((bar) => {
                if (this.selectedData[bar.parentId]) {  //only add the progress bars for the selected data
                    this.progressBars.push(Object.assign({}, bar));
                }
            });
        },

        migrateEntityRequest(entityName, methodName) {
            let params = {
                profile: this.profile.profile,
                gateway: this.profile.gateway,
                credentialFields: this.profile.credentialFields,
                entity: entityName
            };

            return new Promise((resolve, reject) => {
                this.migrationService[methodName](params).then((response) => {
                    this.onProgress(entityName);
                    resolve();
                }).catch((response) => {
                    if (response.response.data && response.response.data.errors) {
                        response.response.data.errors.forEach((error) => {
                            this.addError(error);
                        });
                    }
                    //reject();
                    resolve();
                });
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
