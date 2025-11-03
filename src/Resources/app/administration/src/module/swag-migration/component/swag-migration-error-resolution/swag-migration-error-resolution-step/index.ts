import template from './swag-migration-error-resolution-step.html.twig';
import './swag-migration-error-resolution-step.scss';
import { MIGRATION_API_SERVICE } from '../../../../../core/service/api/swag-migration.api.service';
import type MigrationApiService from '../../../../../core/service/api/swag-migration.api.service';
import { MIGRATION_STORE_ID } from '../../../store/migration.store';
import type { MigrationStore } from '../../../store/migration.store';

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

/**
 * @private
 */
export interface SwagMigrationErrorResolutionStepData {
    defaultTabItem: MigrationLogLevel;
    tablePage: number;
    tableLimit: number;
    tableTotal: number;
    openContinueModal: boolean;
    openErrorResolutionModal: boolean;
    continueLoading: boolean;
    migrationStore: MigrationStore;
    migrationApiService: MigrationApiService;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
    ],

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    data(): SwagMigrationErrorResolutionStepData {
        return {
            defaultTabItem: MIGRATION_LOG_LEVEL.ERROR,
            tablePage: 1,
            tableLimit: 25,
            tableTotal: 145,
            openContinueModal: false,
            openErrorResolutionModal: false,
            continueLoading: false,
            migrationStore: Shopware.Store.get(MIGRATION_STORE_ID),
            migrationApiService: Shopware.Service(MIGRATION_API_SERVICE),
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

    methods: {
        async onContinueMigration() {
            this.continueLoading = true;

            return this.migrationApiService
                .continueAfterErrorResolution()
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc('swag-migration.index.error-resolution.errors.continueMigrationFailed'),
                    });
                })
                .finally(() => {
                    this.continueLoading = false;
                    this.openContinueModal = false;
                });
        },

        async onDownloadLogs() {
            // TODO: fetch latest !?
            const runId = this.migrationStore.latestRun?.id;

            try {
                const blob = await this.migrationApiService.downloadLogsOfRun(runId);

                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');

                link.href = url;
                link.download = `migration-logs-${runId}.txt`;

                document.body.appendChild(link);
                link.click();

                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            } catch (error) {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.downloadLogsFailed'),
                });
            }
        },
    },
});
