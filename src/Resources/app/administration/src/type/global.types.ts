/**
 * @sw-package fundamentals@after-sales
 */
import type { SubContainer } from '@administration/src/global.types';
import type { MigrationStore } from '../module/swag-migration/store/migration.store';
import type MigrationApiService from '../core/service/api/swag-migration.api.service';

declare global {
    interface PiniaRootState {
        swagMigration: MigrationStore;
    }

    interface ServiceContainer extends SubContainer<'service'> {
        migrationApiService: MigrationApiService;
    }
}
