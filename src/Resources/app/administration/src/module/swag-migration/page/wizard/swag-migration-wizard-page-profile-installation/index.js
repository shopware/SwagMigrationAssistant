import template from './swag-migration-wizard-page-profile-installation.html.twig';
import './swag-migration-wizard-page-profile-installation.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-wizard-page-profile-installation', {
    template,

    inject: ['storeService', 'extensionHelperService', 'cacheApiService', 'repositoryFactory'],

    data() {
        return {
            pluginIsLoading: false,
            pluginIsSaveSuccessful: false,
            isInstalled: false,
            pluginName: 'SwagMigrationMagento',
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        version() {
            const version = Shopware.Context.app.config.version;

            const match = version.split('.');

            return [match[0], match[1]].join('.');
        },

        pluginRepository() {
            return this.repositoryFactory.create('plugin');
        },

        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
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

            return this.extensionHelperService.downloadStoreExtension(this.pluginName)
                .then(() => {
                    this.pluginIsSaveSuccessful = true;

                    return this.extensionHelperService.installStoreExtension(this.pluginName, 'plugin');
                })
                .then(() => {
                    return this.extensionHelperService.activateStoreExtension(this.pluginName, 'plugin');
                })
                .finally(() => {
                    this.pluginIsLoading = false;
                    this.cacheApiService.clear().then(() => {
                        window.location.reload();
                    });
                });
        },
    },
});
