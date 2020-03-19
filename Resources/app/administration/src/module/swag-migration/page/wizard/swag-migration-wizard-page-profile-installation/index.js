import template from './swag-migration-wizard-page-profile-installation.html.twig';
import './swag-migration-wizard-page-profile-installation.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-migration-wizard-page-profile-installation', {
    template,

    inject: ['storeService', 'pluginService', 'cacheApiService', 'repositoryFactory'],

    data() {
        return {
            pluginIsLoading: false,
            pluginIsSaveSuccessful: false,
            isInstalled: false,
            pluginName: 'SwagMigrationMagento'
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        pluginRepository() {
            return this.repositoryFactory.create('plugin');
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.refreshPlugin();
        },

        refreshPlugin() {
            const pluginCriteria = new Criteria();
            pluginCriteria.addFilter(Criteria.equals('plugin.name', this.pluginName))
                .addFilter(Criteria.equals('plugin.active', true))
                .setLimit(1);

            return this.pluginRepository.search(pluginCriteria, Shopware.Context.api).then((result) => {
                if (result.total < 1) {
                    return;
                }

                this.isInstalled = true;
            });
        },

        onInstall() {
            this.setupPlugin();
        },

        setupPlugin() {
            this.pluginIsLoading = true;
            this.pluginIsSaveSuccessful = false;

            return this.storeService.downloadPlugin(this.pluginName, true)
                .then(() => {
                    this.pluginIsSaveSuccessful = true;

                    return this.pluginService.install(this.pluginName);
                })
                .then(() => {
                    return this.pluginService.activate(this.pluginName);
                })
                .finally(() => {
                    this.pluginIsLoading = false;
                    this.cacheApiService.clear().then(() => {
                        window.location.reload();
                    });
                });
        }
    }
});
