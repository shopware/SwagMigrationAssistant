import MigrationApiService from './api/swag-migration.api.service';

const { Application } = Shopware;

/**
 * @package services-settings
 * @private
 */

Application.addServiceProvider('migrationApiService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationApiService(initContainer.httpClient, container.loginService);
});
