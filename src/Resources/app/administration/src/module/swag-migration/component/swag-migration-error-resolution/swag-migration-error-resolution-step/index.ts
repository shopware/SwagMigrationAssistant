import template from './swag-migration-error-resolution-step.html.twig';
import './swag-migration-error-resolution-step.scss';

/**
 * @private
 */
export const MIGRATION_LOG_LEVEL = {
    INFO: 'info',
    WARNING: 'warning',
    ERROR: 'error',
} as const;

/**
 * @private
 */
export type MigrationLogLevel = (typeof MIGRATION_LOG_LEVEL)[keyof typeof MIGRATION_LOG_LEVEL];

export interface SwagMigrationErrorResolutionStepData {
    defaultTabItem: MigrationLogLevel;
    tablePage: number;
    tableLimit: number;
    tableTotal: number;
    openContinueModal: boolean;
    openErrorResolutionModal: boolean;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    data(): SwagMigrationErrorResolutionStepData {
        return {
            defaultTabItem: MIGRATION_LOG_LEVEL.ERROR,
            tablePage: 1,
            tableLimit: 25,
            tableTotal: 145,
            openContinueModal: false,
            openErrorResolutionModal: false,
        };
    },

    computed: {
        tableDataSource() {
            return [
                {
                    id: 'test-id',
                    count: '15 / 15',
                    resolved: true,
                    error: 'empty_required_field',
                    entity: 'product',
                    field: 'name',
                },
                {
                    id: 'test-id',
                    count: '15 / 15',
                    resolved: true,
                    error: 'empty_required_field',
                    entity: 'product',
                    field: 'name',
                },
                {
                    id: 'test-id',
                    count: '12 / 15',
                    resolved: false,
                    error: 'empty_required_field',
                    entity: 'product',
                    field: 'name',
                },
                {
                    id: 'test-id',
                    count: '12 / 15',
                    resolved: false,
                    error: 'empty_required_field',
                    entity: 'product',
                    field: 'name',
                },
                {
                    id: 'test-id',
                    count: '12 / 15',
                    resolved: false,
                    error: 'empty_required_field',
                    entity: 'product',
                    field: 'name',
                },
                {
                    id: 'test-id',
                    count: '12 / 15',
                    resolved: false,
                    error: 'empty_required_field',
                    entity: 'product',
                    field: 'name',
                },
            ];
        },

        tabItems() {
            return [
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.tabs.errors', { count: 145 }),
                    name: MIGRATION_LOG_LEVEL.ERROR,
                },
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.tabs.warnings', { count: 35 }),
                    name: MIGRATION_LOG_LEVEL.WARNING,
                },
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.tabs.infos', { count: 13 }),
                    name: MIGRATION_LOG_LEVEL.INFO,
                },
            ];
        },

        tableColumns() {
            return [
                {
                    label: 'Fixed / Total',
                    property: 'count',
                    sortable: true,
                    position: 1,
                },
                {
                    label: 'Error',
                    property: 'error',
                    sortable: true,
                    position: 2,
                },
                {
                    label: 'Entity',
                    property: 'entity',
                    sortable: true,
                    position: 3,
                },
                {
                    label: 'Field',
                    property: 'field',
                    sortable: true,
                    position: 4,
                },
            ];
        },
    },
});
