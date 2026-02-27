import template from './swag-migration-error-resolution-step.html.twig';
import './swag-migration-error-resolution-step.scss';
import { MIGRATION_API_SERVICE, MIGRATION_STEP } from '../../../../../core/service/api/swag-migration.api.service';
import { MIGRATION_STORE_ID } from '../../../store/migration.store';
import type { MigrationStore } from '../../../store/migration.store';
import type { TRepository } from '../../../../../type/types';
import type { LogFilterValue } from '../swag-migration-error-resolution-log-filter';
import { MIGRATION_ERROR_RESOLUTION_SERVICE } from '../../../service/swag-migration-error-resolution.service';

const { Criteria } = Shopware.Data;

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
export type ErrorResolutionTableData = {
    count: number;
    fixCount: number;
    code: string;
    entityName: string;
    fieldName: string;
    resolved: boolean;
};

/**
 * @private
 */
export interface SwagMigrationErrorResolutionStepData {
    tabItem: MigrationLogLevel;
    tablePage: number;
    tableLimit: number;
    tableTotal: number;
    tableSortBy: string;
    tableSortDirection: 'ASC' | 'DESC';
    tableData: Array<ErrorResolutionTableData>;
    loading: boolean;
    downloadLoading: boolean;
    openContinueModal: boolean;
    openErrorResolutionModal: boolean;
    continueLoading: boolean;
    runId: string | null;
    selectedLog: ErrorResolutionTableData | null;
    totalUnfixableErrors: number;
    totalUnresolvedErrors: number;
    migrationStore: MigrationStore;
    logFilter: LogFilterValue;
    levelCounts: {
        error: number;
        warning: number;
        info: number;
    };
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
        MIGRATION_ERROR_RESOLUTION_SERVICE,
        'repositoryFactory',
    ],

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    data(): SwagMigrationErrorResolutionStepData {
        return {
            tabItem: MIGRATION_LOG_LEVEL.ERROR,
            tablePage: 1,
            tableLimit: 25,
            tableTotal: 0,
            tableSortBy: 'count',
            tableSortDirection: 'DESC',
            tableData: [],
            loading: false,
            downloadLoading: false,
            continueLoading: false,
            openContinueModal: false,
            openErrorResolutionModal: false,
            runId: null,
            selectedLog: null,
            totalUnfixableErrors: 0,
            totalUnresolvedErrors: 0,
            migrationStore: Shopware.Store.get(MIGRATION_STORE_ID),
            levelCounts: {
                error: 0,
                warning: 0,
                info: 0,
            },
            logFilter: {
                code: null,
                status: null,
                entity: null,
                field: null,
            },
        };
    },

    created() {
        this.componentCreated();
    },

    computed: {
        migrationLoggingRepository(): TRepository<'swag_migration_logging'> {
            return this.repositoryFactory.create('swag_migration_logging');
        },

        migrationRunRepository(): TRepository<'swag_migration_run'> {
            return this.repositoryFactory.create('swag_migration_run');
        },

        continueMigrationButtonTooltip() {
            if (this.totalUnresolvedErrors > 0) {
                return {
                    message: this.$tc('swag-migration.index.error-resolution.step.header.continueTooltip'),
                    disabled: false,
                };
            }

            return {
                message: '',
                disabled: true,
            };
        },

        continueMigrationWarningText() {
            if (this.totalUnresolvedErrors > 0) {
                return this.$tc('swag-migration.index.error-resolution.step.continue-modal.text-fixable', {
                    count: this.totalUnresolvedErrors,
                });
            }

            if (this.totalUnfixableErrors > 0) {
                return this.$tc('swag-migration.index.error-resolution.step.continue-modal.text-unfixable', {
                    count: this.totalUnfixableErrors,
                });
            }

            return '';
        },

        tabItems() {
            return [
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.tabs.errors', {
                        count: this.levelCounts.error,
                    }),
                    name: MIGRATION_LOG_LEVEL.ERROR,
                    disabled: this.levelCounts.error === 0 || this.loading,
                },
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.tabs.warnings', {
                        count: this.levelCounts.warning,
                    }),
                    name: MIGRATION_LOG_LEVEL.WARNING,
                    disabled: this.levelCounts.warning === 0 || this.loading,
                },
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.tabs.infos', {
                        count: this.levelCounts.info,
                    }),
                    name: MIGRATION_LOG_LEVEL.INFO,
                    disabled: this.levelCounts.info === 0 || this.loading,
                },
            ];
        },

        tableColumns() {
            return [
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.table.columns.count'),
                    property: 'count',
                    sortable: true,
                    position: 1,
                },
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.table.columns.name'),
                    property: 'name',
                    sortable: true,
                    position: 2,
                },
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.table.columns.entity'),
                    property: 'entityName',
                    sortable: true,
                    position: 3,
                },
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.table.columns.field'),
                    property: 'fieldName',
                    sortable: true,
                    position: 4,
                },
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.table.columns.code'),
                    property: 'code',
                    sortable: true,
                    visible: false,
                },
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.table.columns.profileName'),
                    property: 'profileName',
                    sortable: true,
                    visible: false,
                },
                {
                    label: this.$tc('swag-migration.index.error-resolution.step.card.table.columns.gatewayName'),
                    property: 'gatewayName',
                    sortable: true,
                    visible: false,
                },
            ];
        },
    },

    methods: {
        async componentCreated() {
            this.loading = true;

            await this.fetchRun();

            await Promise.all([
                this.fetchLogByLevel(null),
                this.fetchTotalUnfixableErrors(),
            ]);

            if (this.tableTotal === 0 && this.totalUnfixableErrors === 0) {
                await this.commitContinueMigration();
            }
        },

        async fetchTotalUnfixableErrors() {
            if (!this.runId) {
                return;
            }

            try {
                const criteria = new Criteria(1, 1)
                    .addFilter(Criteria.equals('userFixable', false))
                    .addFilter(Criteria.equals('runId', this.runId))
                    .addFilter(
                        Criteria.equalsAny('level', [
                            MIGRATION_LOG_LEVEL.ERROR,
                            MIGRATION_LOG_LEVEL.WARNING,
                        ]),
                    )
                    .addIncludes({ swag_migration_logging: ['id'] });

                const result = await this.migrationLoggingRepository.search(criteria);
                this.totalUnfixableErrors = result.total;
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.fetchUnfixableErrorsFailed'),
                });
            }
        },

        async fetchLogByLevel(level: MigrationLogLevel | null) {
            if (level) {
                this.tabItem = level;
            }

            if (!this.runId) {
                this.loading = false;
                return;
            }

            this.loading = true;

            try {
                const result = await this.migrationApiService.getLogGroups(
                    this.runId,
                    this.tabItem,
                    Number(this.tablePage),
                    Number(this.tableLimit),
                    this.tableSortBy,
                    this.tableSortDirection,
                    this.logFilter,
                );

                this.tableTotal = result.total;
                this.levelCounts = result.levelCounts;

                this.tableData = result.items.map((item) => ({
                    count: item.count,
                    name: this.swagMigrationErrorResolutionService.translateErrorCode(item.code),
                    fixCount: item.fixCount || 0,
                    resolved: (item.fixCount || 0) === item.count && item.count > 0,
                    entityName: item?.entityName || '-',
                    fieldName: item?.fieldName || '-',
                    code: item.code,
                    profileName: item.profileName,
                    gatewayName: item.gatewayName,
                }));
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.fetchLogsFailed'),
                });
            } finally {
                this.loading = false;
            }
        },

        async fetchRun() {
            try {
                const criteria = new Criteria(1, 1)
                    .addFilter(Criteria.equals('connectionId', this.migrationStore.connectionId))
                    .addFilter(Criteria.equals('step', MIGRATION_STEP.ERROR_RESOLUTION))
                    .addIncludes({ swag_migration_run: ['id'] });

                const result = await this.migrationRunRepository.search(criteria);
                this.runId = result.first()?.id || null;
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.fetchRunFailed'),
                });
            }
        },

        async onContinueMigration() {
            this.continueLoading = true;

            try {
                const result = await this.migrationApiService.getLogGroups(
                    this.runId,
                    MIGRATION_LOG_LEVEL.ERROR,
                    1,
                    1,
                    this.tableSortBy,
                    this.tableSortDirection,
                    { status: 'unresolved' },
                );

                await this.fetchTotalUnfixableErrors();

                if (result?.levelCounts?.error > 0 || this.totalUnfixableErrors > 0) {
                    this.totalUnresolvedErrors = result.levelCounts.error;
                    this.continueLoading = false;
                    this.openContinueModal = true;
                } else {
                    await this.commitContinueMigration();
                }
            } catch {
                this.continueLoading = false;
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.continueMigrationFailed'),
                });
            }
        },

        async commitContinueMigration() {
            this.continueLoading = true;

            try {
                await this.migrationApiService.continueAfterErrorResolution();
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.continueMigrationFailed'),
                });
            } finally {
                this.continueLoading = false;
                this.openContinueModal = false;
            }
        },

        async onDownloadLogs() {
            if (!this.runId) {
                return;
            }

            this.downloadLoading = true;

            try {
                const blob = await this.migrationApiService.downloadLogsOfRun(this.runId);
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');

                link.href = url;
                link.download = `migration-logs-${this.runId}.txt`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.downloadLogsFailed'),
                });
            } finally {
                this.downloadLoading = false;
            }
        },

        async onPageChange(page: { page: number; limit: number }) {
            this.tablePage = page.page;
            this.tableLimit = page.limit;

            await this.fetchLogByLevel(null);
        },

        async onSortColumn(column: { dataIndex: string; sortDirection: 'ASC' | 'DESC' }) {
            if (this.tableSortBy === column.dataIndex) {
                this.tableSortDirection = this.tableSortDirection === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.tableSortBy = column.dataIndex;
                this.tableSortDirection = 'DESC';
            }

            await this.fetchLogByLevel(null);
        },

        async onTabChange(tab: MigrationLogLevel) {
            if (this.tabItem === tab) {
                return;
            }

            this.tablePage = 1;
            await this.fetchLogByLevel(tab);
        },

        onOpenEditLog(log: ErrorResolutionTableData) {
            this.openErrorResolutionModal = true;
            this.selectedLog = log;
        },

        onCloseEditLog() {
            this.openErrorResolutionModal = false;
            this.selectedLog = null;
        },

        async onFixesCreated() {
            await this.fetchLogByLevel(null);
        },

        async onLogFilterChange(filter: LogFilterValue) {
            this.tablePage = 1;
            this.logFilter = {
                code: filter.code,
                status: filter.status,
                entity: filter.entity,
                field: filter.field,
            };

            await this.fetchLogByLevel(null);
        },
    },
});
