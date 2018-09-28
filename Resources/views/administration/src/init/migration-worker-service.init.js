import { Application } from 'src/core/shopware';
import MigrationWorkerService from '../../src/core/service/migration/swag-migration-worker.service';

Application.addServiceProvider('migrationWorkerService', (container) => {
    return new MigrationWorkerService(
        container.migrationService,
        container.swagMigrationDataService,
        container.swagMigrationRunService,
        container.swagMigrationMediaFileService
    );
});
