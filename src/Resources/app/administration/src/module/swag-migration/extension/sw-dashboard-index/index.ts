import template from './sw-dashboard-index.html.twig';
import type { TEntity, TRepository } from '../../../../type/types';
import { MIGRATION_STORE_ID } from '../../store/migration.store';

const { Criteria } = Shopware.Data;

/**
 * @private
 */
export interface SwDashboardData {
    context: unknown;
    runExists: boolean;
    loading: boolean;
    run: TEntity<'swag_migration_run'> | null;
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

            if (!this.acl.can('swag_migration.viewer')) {
                this.loading = false;
                return;
            }

            if (Shopware.Store.get(MIGRATION_STORE_ID).latestRun !== null) {
                this.runExists = true;
                this.loading = false;
                return;
            }

            const items = await this.migrationRunRepository.search(new Criteria(1, 1), this.context);

            this.runExists = items.length > 0;

            if (this.runExists) {
                const item = items[0] as TEntity<'swag_migration_run'>;

                this.run = item;
                Shopware.Store.get(MIGRATION_STORE_ID).setLatestRun(item);
            }

            this.loading = false;
        },
    },
});
