import { Application } from 'src/core/shopware';
import MigrationApiService from '../../src/core/service/api/swag-migration.api.service';
import MigrationRunService from '../../src/core/service/api/swag-migration-run.api.service';
import MigrationDataService from '../../src/core/service/api/swag-migration-data.api.service';
import MediaFileService from '../../src/core/service/api/swag-migration-media-file.api.service';
import MigrationProfileService from '../../src/core/service/api/swag-migration-profile.api.service';
import MigrationLoggingService from '../core/service/api/swag-migration-logging.api.service';

Application.addServiceProvider('migrationService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationApiService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagMigrationRunService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationRunService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagMigrationDataService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationDataService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagMigrationMediaFileService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MediaFileService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagMigrationProfileService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationProfileService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagMigrationLoggingService', (container) => {
    const initContainer = Application.getContainer('init');
    return new MigrationLoggingService(initContainer.httpClient, container.loginService);
});
