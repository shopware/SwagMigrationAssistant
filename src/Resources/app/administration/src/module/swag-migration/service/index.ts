import SwagMigrationErrorResolutionService, {
    MIGRATION_ERROR_RESOLUTION_SERVICE,
} from './swag-migration-error-resolution.service';

const { Application } = Shopware;

/**
 * @sw-package after-sales
 * @private
 */
Application.addServiceProvider(MIGRATION_ERROR_RESOLUTION_SERVICE, () => {
    return new SwagMigrationErrorResolutionService();
});
