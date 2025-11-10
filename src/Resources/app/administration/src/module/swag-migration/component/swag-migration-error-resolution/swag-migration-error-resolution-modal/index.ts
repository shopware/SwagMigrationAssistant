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
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.noLogsSelected'),
                });

                return;
            }

            if (!this.fieldValue) {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.fieldValueNotSet'),
                });

                return;
            }

            const isToMany = this.swagMigrationErrorResolutionService.isToManyAssociationField(
                this.selectedLog.entityName,
                this.selectedLog.fieldName,
            );

            if (isToMany) {
                // for "to many" relations, fieldValue should be an array or EntityCollection
                const isArray = Array.isArray(this.fieldValue);
                const isEntityCollection =
                    this.fieldValue && typeof this.fieldValue === 'object' && 'getIds' in this.fieldValue;

                if (!isArray && !isEntityCollection) {
                    this.createNotificationError({
                        message: this.$tc('swag-migration.index.error-resolution.errors.invalidFieldValueFormat'),
                    });

                    return;
                }
            }

            this.submitLoading = true;

            try {
                const entityIdsFromTableData: string[] = this.tableData
                    .filter((row) => this.selectedLogIds.includes(row.logId))
                    .map((row) => row?.convertedData?.id)
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
                        .map((log: MigrationLog) => log?.convertedData?.id)
                        .filter((id: string | null): id is string => id !== null);
                }

                const entityIds = [
                    ...entityIdsFromTableData,
                    ...entityIdsFromMissingLogs,
                ];

                if (entityIds.length === 0) {
                    this.createNotificationError({
                        message: this.$tc('swag-migration.index.error-resolution.errors.noEntityIdsFound'),
                    });

                    return;
                }

                const entities = entityIds.map((entityId) => {
                    return this.createResolutionEntity(entityId);
                });

                await this.migrationFixRepository.saveAll(entities, Shopware.Context.api);

                await this.fetchLogs();

                // clear selection after successful submission
                this.selectedLogIds = [];

                await this.$nextTick();
                const gridRef = this.$refs.errorResolutionGrid as { resetSelection?: () => void } | undefined;

                if (gridRef?.resetSelection) {
                    gridRef.resetSelection();
                }

                this.$emit('fixes-created');
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.submitResolutionFailed'),
                });
            } finally {
                this.submitLoading = false;
            }
        },

        createResolutionEntity(entityId: string) {
            const entity = this.migrationFixRepository.create();

            entity.connectionId = this.migrationStore.connectionId;
            entity.path = this.selectedLog.fieldName;
            entity.entityName = this.selectedLog.entityName;
            entity.entityId = entityId;

            let valueToSave = this.fieldValue;

            // extract ids if fieldValue is an EntityCollection
            if (this.fieldValue && typeof this.fieldValue === 'object' && 'getIds' in this.fieldValue) {
                valueToSave = (this.fieldValue as { getIds: () => string[] }).getIds();
            }

            entity.value = {
                [this.selectedLog.fieldName]: valueToSave,
            };

            return entity;
        },

        async fetchLogs(): Promise<void> {
            if (!this.selectedLog) {
                return;
            }

            this.loading = true;

            try {
                // get all property names except 'status' to map them later
                const entityFieldProperties = this.tableColumns
                    .filter((column) => column.property !== 'status')
                    .map((column) => column.property);

                const logsResult = await this.migrationLoggingRepository.search(this.loggingCriteria, Shopware.Context.api);

                this.tableTotal = logsResult.total;

                // extract entityIds from logs to fetch only relevant fixes
                const entityIds = logsResult
                    .map((log: MigrationLog) => log?.convertedData?.id)
                    .filter((id: string | null): id is string => id !== null);

                const existingFixes = await this.fetchExistingFixesForEntityIds(entityIds);
                const fixesSet = new Set(existingFixes.map((fix) => fix.entityId));

                this.tableData = logsResult.map((log: MigrationLog) => {
                    const convertedData = log?.convertedData || {};
                    const hasFix = fixesSet.has(convertedData?.id);

                    const row: ResolutionModalRow = {
                        logId: log.id,
                        status: hasFix,
                        convertedData,
                        sourceData: log?.sourceData || {},
                        ...this.swagMigrationErrorResolutionService.mapEntityFieldProperties(
                            this.selectedLog.entityName,
                            entityFieldProperties,
                            convertedData,
                        ),
                    };

                    return row;
                });
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.fetchLogsFailed'),
                });
            } finally {
                this.loading = false;
            }
        },

        async fetchExistingFixesForEntityIds(entityIds: string[]): Promise<Array<{ entityId: string }>> {
            if (!this.selectedLog || entityIds.length === 0) {
                return [];
            }

            try {
                const criteria = new Criteria()
                    .addFilter(Criteria.equals('connectionId', this.migrationStore.connectionId))
                    .addFilter(Criteria.equals('entityName', this.selectedLog.entityName))
                    .addFilter(Criteria.equalsAny('entityId', entityIds))
                    .addIncludes({
                        swag_migration_fix: ['entityId'],
                    });

                const result = await this.migrationFixRepository.search(criteria, Shopware.Context.api);

                return result.map((fix) => ({
                    entityId: fix.entityId,
                }));
            } catch {
                return [];
            }
        },

        async onSelectAllLogs() {
            if (!this.selectedLog) {
                return Promise.resolve();
            }

            this.loading = true;

            return this.migrationApiService
                .getAllLogIds(this.selectedLog.code, this.selectedLog.entityName, this.selectedLog.fieldName)
                .then((result) => {
                    // filter out resolved logs from selection
                    this.selectedLogIds = result.ids.filter((logId) => {
                        const row = this.tableData.find((element) => element.logId === logId);

                        return row && !row.status;
                    });

                    // re-select all rows in the current page
                    this.$nextTick(() => {
                        const gridRef = this.$refs.errorResolutionGrid;

                        if (gridRef && this.tableData.length > 0) {
                            this.tableData.forEach((row) => {
                                if (this.selectedLogIds.includes(row.logId) && !row.status) {
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

            // filter out resolved logs from selection
            const selectableLogIds = selectedIds.filter((logId) => {
                const row = this.tableData.find((element) => element.logId === logId);

                return row && !row.status;
            });

            this.selectedLogIds = [
                ...this.selectedLogIds,
                ...selectableLogIds,
            ];
        },

        isRecordSelectable(item: ResolutionModalRow): boolean {
            return !item.status;
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
