/**
 * @sw-package after-sales
 */
import type { MigrationState } from '../module/swag-migration/store/migration.store';

declare global {
    interface PiniaRootState {
        swagMigration: MigrationState;
    }
}
