import { Application } from 'src/core/shopware';
import MigrationApiService from '../../src/core/service/api/swag-migration.api.service';

Application.addServiceProvider('migrationService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationApiService(initContainer.httpClient, container.loginService);
});
