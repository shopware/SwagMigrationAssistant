import template from './swag-migration-apply-fixes.html.twig';
import { MIGRATION_API_SERVICE } from '../../../../../core/service/api/swag-migration.api.service';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
    ],

    methods: {
        async resumeMigration() {
            await this.migrationApiService.resumeMigrationAfterFixes();
        },
    },
});
