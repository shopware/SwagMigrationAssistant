import MigrationApiService, { MIGRATION_API_SERVICE } from './api/swag-migration.api.service';

const { Application } = Shopware;

/**
 * @sw-package fundamentals@after-sales
 * @private
 */
Application.addServiceProvider(MIGRATION_API_SERVICE, (container: ServiceContainer) => {
    const initContainer = Application.getContainer('init');
    return new MigrationApiService(initContainer.httpClient, container.loginService);
});
