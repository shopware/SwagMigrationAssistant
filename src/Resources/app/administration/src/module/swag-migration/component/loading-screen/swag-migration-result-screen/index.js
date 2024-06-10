import template from './swag-migration-result-screen.html.twig';
import './swag-migration-result-screen.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-result-screen', {
    template,

    inject: {
        repositoryFactory: 'repositoryFactory',
    },

    data() {
        return {
            latestRun: null,
            context: Shopware.Context.api,
        };
    },

    computed: {
        migrationRunRepository() {
            return this.repositoryFactory.create('swag_migration_run');
        },
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        async mountedComponent() {
            this.latestRun = await this.fetchLatestRun();
        },

        async fetchLatestRun() {
            const criteria = new Criteria(1, 1);
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            const results = await this.migrationRunRepository.search(criteria, this.context);
            return results.first();
        },
    },
});
