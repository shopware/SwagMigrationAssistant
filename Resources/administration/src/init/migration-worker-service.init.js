import MigrationWorkerService from '../core/service/migration/swag-migration-worker.service';

const { Application } = Shopware;

Application.addServiceProvider('migrationWorkerService', (container) => {
    return new MigrationWorkerService(
        container.migrationService,
        container.swagMigrationRunService,
        container.swagMigrationLoggingService
    );
});
