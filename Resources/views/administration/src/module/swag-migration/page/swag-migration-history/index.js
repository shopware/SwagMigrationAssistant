import { Component, State, Mixin } from 'src/core/shopware';
import template from './swag-migration-history.html.twig';
import './swag-migration-history.less';

Component.register('swag-migration-history', {
    template,

    mixins: [
        Mixin.getByName('listing')
    ],

    data() {
        return {
            isLoading: false,
            migrationRuns: [],
            sortBy: 'createdAt',
            sortDirection: 'DESC'
        };
    },

    computed: {
        migrationRunStore() {
            return State.getStore('swag_migration_run');
        }
    },

    methods: {
        getList() {
            this.isLoading = true;
            const params = this.getListingParams();
            params.associations = { profile: { limit: 1 } };

            return this.migrationRunStore.getList(params).then((response) => {
                this.total = response.total;
                this.migrationRuns = response.items;
                this.isLoading = false;

                return this.migrationRuns;
            });
        }
    }
});
