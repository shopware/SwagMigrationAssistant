import template from './swag-migration-result-screen.html.twig';
import './swag-migration-result-screen.scss';
import type { TEntity, TRepository } from '../../../../../type/types';

const { Criteria } = Shopware.Data;

/**
 * @private
 */
export interface SwagMigrationResultScreenData {
    latestRun?: TEntity<'swag_migration_run'>;
    context: unknown;
}

/**
 *@private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'repositoryFactory',
    ],

    data(): SwagMigrationResultScreenData {
        return {
            latestRun: null,
            context: Shopware.Context.api,
        };
    },

    computed: {
        migrationRunRepository(): TRepository<'swag_migration_run'> {
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

        async fetchLatestRun(): Promise<TEntity<'swag_migration_run'> | null> {
            const criteria = new Criteria(1, 1);
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            const results = await this.migrationRunRepository.search(criteria, this.context);
            return results.first();
        },
    },
});
