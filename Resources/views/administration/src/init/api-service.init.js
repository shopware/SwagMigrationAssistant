import { Application } from 'src/core/shopware';
import MigrationApiService from '../../src/core/service/api/swag-migration.api.service';
import RunService from '../../src/core/service/api/swag-migration-run.api.service';
import DataService from '../../src/core/service/api/swag-migration-data.api.service';
import MediaFileService from '../../src/core/service/api/swag-migration-media-file.api.service';
import ProfileService from '../../src/core/service/api/swag-migration-profile.api.service';

Application.addServiceProvider('migrationService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationApiService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagMigrationRunService', (container) => {
    const initContainer = Application.getContainer('init');
    return new RunService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagMigrationDataService', (container) => {
    const initContainer = Application.getContainer('init');
    return new DataService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagMigrationMediaFileService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MediaFileService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagMigrationProfileService', (container) => {
    const initContainer = Application.getContainer('init');
    return new ProfileService(initContainer.httpClient, container.loginService);
});
