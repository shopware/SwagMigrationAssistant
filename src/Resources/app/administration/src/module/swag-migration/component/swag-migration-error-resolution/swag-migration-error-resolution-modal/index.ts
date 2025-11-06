import template from './swag-migration-error-resolution-modal.html.twig';
import './swag-migration-error-resolution-modal.scss';
import type { ErrorResolutionTableData } from '../swag-migration-error-resolution-step';
import type { MigrationLog, TRepository } from '../../../../../type/types';
import type { TableColumn } from '../../../service/swag-migration-error-resolution.service';
import { MIGRATION_ERROR_RESOLUTION_SERVICE } from '../../../service/swag-migration-error-resolution.service';
import { MIGRATION_API_SERVICE } from '../../../../../core/service/api/swag-migration.api.service';
import { MIGRATION_STORE_ID, type MigrationStore } from '../../../store/migration.store';

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
    submitLoading: boolean;
    fieldValue: string[] | string | boolean | number | null;
    migrationStore: MigrationStore;
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
            submitLoading: false,
            fieldValue: null,
            migrationStore: Shopware.Store.get(MIGRATION_STORE_ID),
        };
    },

    provide() {
        return {
            updateFieldValue: (value: string[] | string | boolean | number | null) => {
                this.fieldValue = value;
            },
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        migrationLoggingRepository(): TRepository<'swag_migration_logging'> {
            return this.repositoryFactory.create('swag_migration_logging');
        },

        migrationFixRepository(): TRepository<'swag_migration_fix'> {
            return this.repositoryFactory.create('swag_migration_fix');
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

        tableColumns(): TableColumn[] {
            return this.swagMigrationErrorResolutionService.generateTableColumns(
                this.selectedLog.entityName,
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

        async onSubmitResolution() {
            if (this.selectedLogIds.length <= 0) {
                return;
            }

            this.submitLoading = true;

            try {
                const entityIdsFromTableData: string[] = this.tableData
                    .filter((row) => this.selectedLogIds.includes(row.logId))
                    .map((row) => {
                        const convertedData = row.convertedData || {};
                        return convertedData.id ? String(convertedData.id) : null;
                    })
                    .filter((id: string | null): id is string => id !== null);

                const currentPageLogIds = this.tableData.map((row) => row.logId);
                const missingLogIds = this.selectedLogIds.filter((logId) => !currentPageLogIds.includes(logId));

                let entityIdsFromMissingLogs: string[] = [];

                if (missingLogIds.length > 0) {
                    const criteria = new Criteria(1, missingLogIds.length)
                        .addFilter(Criteria.equals('code', this.selectedLog.code))
                        .addFilter(Criteria.equals('entityName', this.selectedLog.entityName))
                        .addFilter(Criteria.equals('fieldName', this.selectedLog.fieldName))
                        .addIncludes({
                            swag_migration_logging: ['convertedData'],
                        })
                        .setIds(missingLogIds);

                    const logs = await this.migrationLoggingRepository.search(criteria, Shopware.Context.api);

                    entityIdsFromMissingLogs = logs
                        .map((log: MigrationLog) => {
                            const convertedData = log?.convertedData || {};
                            return convertedData.id ? String(convertedData.id) : null;
                        })
                        .filter((id: string | null): id is string => id !== null);
                }

                const entityIds = [
                    ...entityIdsFromTableData,
                    ...entityIdsFromMissingLogs,
                ];

                const submitPromises = entityIds.map((entityId) => {
                    return this.submitSingleResolution(entityId);
                });

                await Promise.all(submitPromises);

                // TODO: refresh current table page
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.submitResolutionFailed'),
                });
            } finally {
                this.submitLoading = false;
            }
        },

        async submitSingleResolution(entityId: string) {
            // TODO: use entityId & entityName when backend supports it
            // TODO: remove main_mapping_id

            const entity = this.migrationFixRepository.create();
            entity.connectionId = this.migrationStore.connectionId;
            entity.mainMappingId = '019a583caa7173db943932013bff39d5';
            entity.path = this.selectedLog.fieldName;
            entity.value = {
                [this.selectedLog.fieldName]: this.fieldValue,
            };

            return this.migrationFixRepository.save(entity);
        },

        async fetchLogs() {
            if (!this.selectedLog) {
                return Promise.resolve();
            }

            this.loading = true;

            // get all property names except 'status' to map them later
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

                        // map only the properties that are defined for the current entity field
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

                    // re-select all rows in the current page
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

        statusBadgeClass(isResolved: boolean): string {
            return isResolved
                ? 'swag-migration-error-resolution-modal__left-status--unresolved'
                : 'swag-migration-error-resolution-modal__left-status--resolved';
        },

        statusBadgeText(isResolved: boolean): string {
            return isResolved
                ? this.$tc('swag-migration.index.error-resolution.modals.error.left.status.resolved')
                : this.$tc('swag-migration.index.error-resolution.modals.error.left.status.unresolved');
        },

        onSelectionChanged(selection: Record<string, ResolutionModalRow>) {
            // clear current page selections if no selection
            if (!selection || Object.keys(selection).length === 0) {
                this.selectedLogIds = [];
                return;
            }

            const currentPageIds = this.tableData.map((row) => row.logId);

            // remove deselected ids from current page
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
