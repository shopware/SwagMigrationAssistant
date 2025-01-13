import MigrationApiService from './api/swag-migration.api.service';

const { Application } = Shopware;

/**
 * @sw-package fundamentals@after-sales
 * @private
 */

Application.addServiceProvider('migrationApiService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationApiService(initContainer.httpClient, container.loginService);
});
