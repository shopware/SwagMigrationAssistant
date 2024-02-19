import MigrationApiService from './api/swag-migration.api.service';
import ProcessStoreInitService from './migration/swag-migration-process-store-init.service';
import UiStoreInitService from './migration/swag-migration-ui-store-init.service';

const { Application } = Shopware;

/**
 * @package services-settings
 * @private
 */

Application.addServiceProvider('migrationApiService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationApiService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('processStoreInitService', (container) => {
    return new ProcessStoreInitService(
        container.migrationApiService,
        container.repositoryFactory,
        Shopware.Context.api,
    );
});

Application.addServiceProvider('uiStoreInitService', (container) => {
    return new UiStoreInitService(container.migrationApiService);
});
