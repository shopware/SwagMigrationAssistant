import template from './swag-migration-main-page.html.twig';
import './swag-migration-main-page.scss';
import type { MigrationStore } from '../../store/migration.store';
import { MIGRATION_STORE_ID } from '../../store/migration.store';

/**
 * @private
 */
export interface SwagMigrationMainPageData {
    migrationStore: MigrationStore;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    data(): SwagMigrationMainPageData {
        return {
            migrationStore: Shopware.Store.get(MIGRATION_STORE_ID),
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        environmentInformation() {
            return this.migrationStore.environmentInformation;
        },

        connectionId() {
            return this.migrationStore.connectionId;
        },

        isLoading() {
            return this.migrationStore.isLoading;
        },

        displayWarnings() {
            return this.environmentInformation.displayWarnings;
        },

        connectionEstablished() {
            return (
                this.environmentInformation !== undefined &&
                this.environmentInformation.requestStatus &&
                (this.environmentInformation.requestStatus.isWarning === true ||
                    (this.environmentInformation.requestStatus.isWarning === false &&
                        this.environmentInformation.requestStatus.code === ''))
            );
        },
    },
});
