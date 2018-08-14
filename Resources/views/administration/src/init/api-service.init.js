import { Application } from 'src/core/shopware';
import MigrationApiService from '../../src/core/service/api/swag-migration.api.service';
import ProfileService from '../../src/core/service/api/swag-migration-profile.api.service';


Application.addServiceProvider('migrationService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationApiService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('migrationProfileService', (container) => {
    const initContainer = Application.getContainer('init');
    return new ProfileService(initContainer.httpClient, container.loginService);
});
