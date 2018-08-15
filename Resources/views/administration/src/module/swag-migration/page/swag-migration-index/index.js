import { Component, State } from 'src/core/shopware';
import template from './swag-migration-index.html.twig';
import './swag-migration-index.less';

Component.register('swag-migration-index', {
    template,

    mixins: [
        'notification'
    ],

    inject: ['migrationProfileService', 'migrationService', 'catalogService'],

    data() {
        return {
            profile: {},
            componentIndex: 0,
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
                    entityNames: ['category', 'product', 'translation'],
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
            progressBars: [
                {
                    entityNames: ['customer', 'order'],
                    title: this.$tc('swag-migration.index.selectDataCard.dataPossible.customersAndOrders'),
                    value: 0
                },
                {
                    entityNames: ['category', 'product', 'translation'],
                    title: this.$tc('swag-migration.index.selectDataCard.dataPossible.categoriesAndProducts'),
                    value: 0
                },
                {
                    entityNames: ['media'],
                    title: this.$tc('swag-migration.index.selectDataCard.dataPossible.media'),
                    value: 0
                }
            ],
        };
    },

    created() {
        const params = {
            offset: 0,
            limit: 100,
            additionalParams: {
                term: { gateway: 'api' }
            }
        };

        //Get profile with credentials from server
        this.migrationProfileService.getList(params).then((response) => {
            this.profile = response.data[0];

            //check if credentials are given
            if (!this.profile.credentialFields.endpoint || !this.profile.credentialFields.apiUser || !this.profile.credentialFields.apiKey ) {
                this.$router.push({ name: 'swag.migration.wizard.introduction' });
                return;
            }


            //Do connection check
            this.migrationService.checkConnection(this.profile.id).then((connectionCheckResponse) => {
                if (!connectionCheckResponse.success) {
                    this.$router.push({ name: 'swag.migration.wizard.credentials' });
                }
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
        async onMigrate() {
            this.selectedData = this.$refs.dataSelector.getSelectedData();

            this.componentIndex = 1;    //show fetch loading screen

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
            this.progressBars.shift();
            this.progressBars.shift();

            this.progressBars[0].value = 100;
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
            this.progressBars.forEach((bar) => {
                bar.value = 0;
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
                    this.createNotificationSuccess({message: 'Success: ' + entityName});
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
        }
    }
});
