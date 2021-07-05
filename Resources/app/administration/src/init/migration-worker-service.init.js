import MigrationWorkerService from '../core/service/migration/swag-migration-worker.service';
import MigrationIndexingWorker from '../core/service/migration/swag-migration-indexing-worker.service';

const { Application } = Shopware;

Application.addServiceProvider('migrationWorkerService', (container) => {
    return new MigrationWorkerService(
        container.migrationService,
        container.migrationIndexingWorkerService,
    );
});

Application.addServiceProvider('migrationIndexingWorkerService', (container) => {
    return new MigrationIndexingWorker(
        container.migrationIndexingApiService,
    );
});
