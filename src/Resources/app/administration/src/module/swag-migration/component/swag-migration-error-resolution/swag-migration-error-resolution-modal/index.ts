import template from './swag-migration-error-resolution-modal.html.twig';
import './swag-migration-error-resolution-modal.scss';
import type { ErrorResolutionTableData } from '../swag-migration-error-resolution-step';
import type { MigrationLog, TRepository } from '../../../../../type/types';
import type { EntityFields, TableColumn } from '../../../service/swag-migration-error-resolution.service';
import { MIGRATION_ERROR_RESOLUTION_SERVICE } from '../../../service/swag-migration-error-resolution.service';
import { MIGRATION_API_SERVICE } from '../../../../../core/service/api/swag-migration.api.service';
import type MigrationApiService from '../../../../../core/service/api/swag-migration.api.service';

const { Criteria } = Shopware.Data;

/**
 * @private
 */
export type ResolutionModalRow = {
    status: boolean;
    convertedData: Record<string, unknown>;
    sourceData: Record<string, unknown>;
} & Record<string, unknown>;

/**
 * @private
 */
export interface SwagMigrationErrorResolutionModalData {
    openDetailsModal: boolean;
    tablePage: number;
    tableLimit: number;
    tableTotal: number;
    tableData: ResolutionModalRow[];
    selectedLogIds: string[];
    selectedDetailsLog: ResolutionModalRow;
    loading: boolean;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'repositoryFactory',
        MIGRATION_ERROR_RESOLUTION_SERVICE,
        MIGRATION_API_SERVICE,
    ],

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    props: {
        selectedLog: {
            type: Object as PropType<ErrorResolutionTableData>,
            required: true,
        },
    },

    data(): SwagMigrationErrorResolutionModalData {
        return {
            openDetailsModal: false,
            tablePage: 1,
            tableLimit: 25,
            tableTotal: 0,
            tableData: [],
            selectedLogIds: [],
            selectedDetailsLog: null,
            loading: false,
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        migrationLoggingRepository(): TRepository<'swag_migration_logging'> {
            return this.repositoryFactory.create('swag_migration_logging');
        },

        migrationApiService(): MigrationApiService {
            return Shopware.Service(MIGRATION_API_SERVICE);
        },

        loggingCriteria() {
            return new Criteria(this.tablePage, this.tableLimit)
                .addFilter(Criteria.equals('code', this.selectedLog.code))
                .addFilter(Criteria.equals('entityName', this.selectedLog.entityName))
                .addFilter(Criteria.equals('fieldName', this.selectedLog.fieldName));
        },

        modalTitle() {
            return this.$tc('swag-migration.index.error-resolution.modals.error.title', {
                code: this.selectedLog.code,
                entityName: this.selectedLog.entityName,
                fieldName: this.selectedLog.fieldName,
            });
        },

        entityFields(): EntityFields {
            return this.swagMigrationErrorResolutionService.extractEntityFields(this.selectedLog.entityName);
        },

        tableColumns(): TableColumn[] {
            return this.swagMigrationErrorResolutionService.generateTableColumns(
                this.entityFields,
                this.selectedLog.fieldName,
            );
        },

        preSelection(): Record<string, ResolutionModalRow> {
            const selection: Record<string, ResolutionModalRow> = {};

            if (this.selectedLogIds.length === 0) {
                return selection;
            }

            this.tableData.forEach((row) => {
                if (this.selectedLogIds.includes(row.logId)) {
                    selection[row.logId] = row;
                }
            });

            return selection;
        },
    },

    methods: {
        async createdComponent() {
            await this.fetchLogs();
        },

        async fetchLogs() {
            if (!this.selectedLog) {
                return Promise.resolve();
            }

            this.loading = true;

            const entityFieldProperties = this.tableColumns
                .filter((column) => column.property !== 'status')
                .map((column) => column.property);

            return this.migrationLoggingRepository
                .search(this.loggingCriteria, Shopware.Context.api)
                .then((result) => {
                    this.tableTotal = result.total;

                    this.tableData = result.map((log: MigrationLog) => {
                        const convertedData = log?.convertedData || {};

                        const row: ResolutionModalRow = {
                            logId: log.id,
                            status: false,
                            convertedData,
                            sourceData: log?.sourceData || {},
                        };

                        entityFieldProperties.forEach((property) => {
                            if (property in convertedData) {
                                row[property] = convertedData[property];
                            }
                        });

                        return row;
                    });
                })
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc('swag-migration.index.error-resolution.errors.fetchLogsFailed'),
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        async onSelectAllLogs() {
            if (!this.selectedLog) {
                return Promise.resolve();
            }

            this.loading = true;

            return this.migrationApiService
                .getAllLogIds(this.selectedLog.code, this.selectedLog.entityName, this.selectedLog.fieldName)
                .then((result) => {
                    this.selectedLogIds = result.ids;

                    this.$nextTick(() => {
                        const gridRef = this.$refs.errorResolutionGrid;

                        if (gridRef && this.tableData.length > 0) {
                            this.tableData.forEach((row) => {
                                if (this.selectedLogIds.includes(row.logId)) {
                                    gridRef.selectItem(true, row);
                                }
                            });
                        }
                    });
                })
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc('swag-migration.index.error-resolution.errors.fetchLogsFailed'),
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        statusBadgeClass(status: boolean) {
            return status
                ? 'swag-migration-error-resolution-modal__left-status--unresolved'
                : 'swag-migration-error-resolution-modal__left-status--resolved';
        },

        statusBadgeText(status: boolean) {
            return status
                ? this.$tc('swag-migration.index.error-resolution.modals.error.left.status.resolved')
                : this.$tc('swag-migration.index.error-resolution.modals.error.left.status.unresolved');
        },

        onSelectionChanged(selection: Record<string, ResolutionModalRow>) {
            const currentPageIds = this.tableData.map((row) => row.logId);

            this.selectedLogIds = this.selectedLogIds.filter((id) => !currentPageIds.includes(id));

            const selectedIds = Object.keys(selection);
            this.selectedLogIds = [
                ...this.selectedLogIds,
                ...selectedIds,
            ];
        },

        onOpenDetailsModal(row: ResolutionModalRow) {
            this.selectedDetailsLog = row;
            this.openDetailsModal = true;
        },

        onCloseDetailsModal() {
            this.openDetailsModal = false;
            this.selectedDetailsLog = null;
        },

        async onPageChange(page: { page: number; limit: number }) {
            this.tablePage = page.page;
            this.tableLimit = page.limit;

            await this.fetchLogs();
        },
    },
});
