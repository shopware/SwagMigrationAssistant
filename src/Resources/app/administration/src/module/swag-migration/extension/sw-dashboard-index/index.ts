import template from './sw-dashboard-index.html.twig';
import type { TEntity, TRepository } from '../../../../type/types';

const { Criteria } = Shopware.Data;

export interface SwDashboardData {
    context: unknown;
    runExists: boolean;
    loading: boolean;
    run?: TEntity<'swag_migration_run'>;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    data(): SwDashboardData {
        return {
            context: Shopware.Context.api,
            runExists: false,
            loading: true,
            run: null,
        };
    },

    computed: {
        migrationRunRepository(): TRepository<'swag_migration_run'> {
            return this.repositoryFactory.create('swag_migration_run');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.$super('createdComponent');

            if (!this.acl.isAdmin()) {
                this.loading = false;
                return;
            }

            const items = await this.migrationRunRepository.search(new Criteria(1, 1), this.context);

            this.runExists = items.length > 0;

            if (this.runExists) {
                this.run = items[0] as TEntity<'swag_migration_run'>;
            }

            this.loading = false;
        },
    },
});
