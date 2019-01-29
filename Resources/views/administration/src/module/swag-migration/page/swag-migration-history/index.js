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
            sortDirection: 'DESC',
            oldParams: {}
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
            if (JSON.stringify(this.oldParams) === JSON.stringify(params)) {
                // Do not request the data again if the parameters don't change.
                // For example if the detail window (child route) is opened.
                this.isLoading = false;
                return Promise.resolve();
            }

            this.oldParams = params;
            return this.migrationRunStore.getList(params).then((response) => {
                this.total = response.total;
                this.migrationRuns = response.items;
                this.isLoading = false;
            });
        }
    }
});
