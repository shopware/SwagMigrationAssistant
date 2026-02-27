import template from './swag-migration-index.html.twig';
import { MIGRATION_STORE_ID } from '../../store/migration.store';
import type { TRepository } from '../../../../type/types';

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

    computed: {
        migrationRunRepository(): TRepository<'swag_migration_run'> {
            return this.repositoryFactory.create('swag_migration_run');
        },

        hasHistory() {
            return Shopware.Store.get(MIGRATION_STORE_ID).latestRun !== null;
        },
    },

    methods: {
        async createdComponent() {
            await this.$super('createdComponent');

            if (Shopware.Store.get(MIGRATION_STORE_ID).latestRun !== null) {
                return;
            }

            const criteria = new Shopware.Data.Criteria(1, 1);

            await this.migrationRunRepository.search(criteria).then((response) => {
                if (response.first()) {
                    Shopware.Store.get(MIGRATION_STORE_ID).setLatestRun(response.first());
                }
            });
        },

        setActiveTab(tabItem: { name: string }) {
            this.$router.push({ name: tabItem.name });
        },
    },
});
