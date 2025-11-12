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

        tableIdentifier(): string {
            // unique identifier for each modal grid based on selected log, to avoid selection conflicts
            return `swag-migration-error-resolution-modal-grid-${this.selectedLog.entityName}-${this.selectedLog.fieldName}`;
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
            if (this.selectedLogIds.length === 0) {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.noLogsSelected'),
                });

                return;
            }

            const validationError = this.swagMigrationErrorResolutionService.validateFieldValue(
                this.selectedLog.entityName,
                this.selectedLog.fieldName,
                this.fieldValue,
            );

            if (validationError) {
                this.createNotificationError({
                    message: this.$tc(`swag-migration.index.error-resolution.errors.${validationError}`),
                });

                return;
            }

            this.submitLoading = true;

            try {
                const entityIds = await this.collectEntityIdsForSubmission();

                if (entityIds.length === 0) {
                    this.createNotificationError({
                        message: this.$tc('swag-migration.index.error-resolution.errors.noEntityIdsFound'),
                    });

                    return;
                }

                const entities = entityIds.map((entityId) => this.createResolutionEntity(entityId));

                await this.migrationFixRepository.saveAll(entities);
                await this.fetchLogs();

                this.resetSelection();

                this.$emit('fixes-created');
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.submitResolutionFailed'),
                });
            } finally {
                this.submitLoading = false;
            }
        },

        async collectEntityIdsForSubmission(): Promise<string[]> {
            const entityIdsFromTableData = this.extractEntityIdsFromTableData();
            const missingLogIds = this.getMissingLogIds();

            if (missingLogIds.length === 0) {
                return entityIdsFromTableData;
            }

            const entityIdsFromMissingLogs = await this.fetchEntityIdsFromMissingLogs(missingLogIds);

            return [
                ...entityIdsFromTableData,
                ...entityIdsFromMissingLogs,
            ];
        },

        extractEntityIdsFromTableData(): string[] {
            return this.tableData
                .filter((row) => this.selectedLogIds.includes(row.logId))
                .map((row) => row?.convertedData?.id)
                .filter((id: string | null): id is string => id !== null);
        },

        getMissingLogIds(): string[] {
            const currentPageLogIds = this.tableData.map((row) => row.logId);

            return this.selectedLogIds.filter((logId) => !currentPageLogIds.includes(logId));
        },

        async fetchEntityIdsFromMissingLogs(missingLogIds: string[]): Promise<string[]> {
            const criteria = new Criteria(1, missingLogIds.length)
                .addFilter(Criteria.equals('code', this.selectedLog.code))
                .addFilter(Criteria.equals('entityName', this.selectedLog.entityName))
                .addFilter(Criteria.equals('fieldName', this.selectedLog.fieldName))
                .addIncludes({
                    swag_migration_logging: ['convertedData'],
                })
                .setIds(missingLogIds);

            const logs = await this.migrationLoggingRepository.search(criteria);

            return logs
                .map((log: MigrationLog) => log?.convertedData?.id)
                .filter((id: string | null): id is string => id !== null);
        },

        resetSelection() {
            this.selectedLogIds = [];

            this.$nextTick(() => {
                const gridRef = this.$refs.errorResolutionGrid as { resetSelection?: () => void } | undefined;

                if (gridRef?.resetSelection) {
                    gridRef.resetSelection();
                }
            });
        },

        createResolutionEntity(entityId: string) {
            const entity = this.migrationFixRepository.create();

            entity.connectionId = this.migrationStore.connectionId;
            entity.path = this.selectedLog.fieldName;
            entity.entityName = this.selectedLog.entityName;
            entity.entityId = entityId;

            const normalizedValue = this.swagMigrationErrorResolutionService.normalizeFieldValueForSave(this.fieldValue);

            entity.value = {
                [this.selectedLog.fieldName]: normalizedValue,
            };

            return entity;
        },

        async fetchLogs(): Promise<void> {
            if (!this.selectedLog) {
                return;
            }

            this.loading = true;

            try {
                const entityFieldProperties = this.getEntityFieldProperties();

                const logsResult = await this.migrationLoggingRepository.search(this.loggingCriteria);

                this.tableTotal = logsResult.total;

                const entityIds = this.extractEntityIdsFromLogs(logsResult);
                const fixesMap = await this.buildFixesMap(entityIds);

                this.tableData = this.mapLogsToTableData(logsResult, fixesMap, entityFieldProperties);
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.fetchLogsFailed'),
                });
            } finally {
                this.loading = false;
            }
        },

        getEntityFieldProperties(): string[] {
            return this.tableColumns.filter((column) => column.property !== 'status').map((column) => column.property);
        },

        extractEntityIdsFromLogs(logs: MigrationLog[]): string[] {
            return logs
                .map((log: MigrationLog) => log?.convertedData?.id)
                .filter((id: string | null): id is string => id !== null);
        },

        async buildFixesMap(entityIds: string[]): Promise<Map<string, Record<string, unknown>>> {
            const existingFixes = await this.fetchExistingFixesForEntityIds(entityIds);

            return new Map(
                existingFixes.map((fix) => [
                    fix.entityId,
                    fix.value,
                ]),
            );
        },

        mapLogsToTableData(
            logs: MigrationLog[],
            fixesMap: Map<string, Record<string, unknown>>,
            entityFieldProperties: string[],
        ): ResolutionModalRow[] {
            return logs.map((log: MigrationLog) => {
                const convertedData = log?.convertedData || {};

                const entityId = convertedData?.id as string | undefined;
                const fixValue = entityId ? fixesMap.get(entityId) : undefined;
                const hasFix = this.isLogResolved(fixValue);

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

                if (hasFix && fixValue) {
                    row[this.selectedLog.fieldName] = fixValue[this.selectedLog.fieldName];
                }

                return row;
            });
        },

        isLogResolved(fixValue: Record<string, unknown> | undefined): boolean {
            return !!(fixValue && Object.prototype.hasOwnProperty.call(fixValue, this.selectedLog.fieldName));
        },

        filterUnresolvedLogIds(logIds: string[]): string[] {
            return logIds.filter((logId) => {
                const row = this.tableData.find((element) => element.logId === logId);

                return row && !row.status;
            });
        },

        async fetchExistingFixesForEntityIds(
            entityIds: string[],
        ): Promise<Array<{ entityId: string; value: Record<string, unknown> }>> {
            if (!this.selectedLog || entityIds.length === 0) {
                return [];
            }

            try {
                const criteria = new Criteria()
                    .addFilter(Criteria.equals('connectionId', this.migrationStore.connectionId))
                    .addFilter(Criteria.equals('entityName', this.selectedLog.entityName))
                    .addFilter(Criteria.equals('path', this.selectedLog.fieldName))
                    .addFilter(Criteria.equalsAny('entityId', entityIds))
                    .addIncludes({
                        swag_migration_fix: [
                            'entityId',
                            'value',
                        ],
                    });

                const result = await this.migrationFixRepository.search(criteria);

                return result.map((fix) => ({
                    entityId: fix.entityId,
                    value: fix.value || {},
                }));
            } catch {
                return [];
            }
        },

        async onSelectAllLogs() {
            if (!this.selectedLog) {
                return;
            }

            this.loading = true;

            try {
                const result = await this.migrationApiService.getAllLogIds(
                    this.selectedLog.code,
                    this.selectedLog.entityName,
                    this.selectedLog.fieldName,
                );

                this.selectedLogIds = this.filterUnresolvedLogIds(result.ids);

                await this.$nextTick();
                this.applySelectionToGrid();
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.fetchLogsFailed'),
                });
            } finally {
                this.loading = false;
            }
        },

        applySelectionToGrid() {
            const gridRef = this.$refs.errorResolutionGrid;

            if (!gridRef || this.tableData.length === 0) {
                return;
            }

            this.tableData.forEach((row) => {
                if (this.selectedLogIds.includes(row.logId) && !row.status) {
                    gridRef.selectItem(true, row);
                }
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
            if (!selection || Object.keys(selection).length === 0) {
                this.selectedLogIds = [];

                return;
            }

            const currentPageIds = this.tableData.map((row) => row.logId);
            const idsFromOtherPages = this.selectedLogIds.filter((id) => !currentPageIds.includes(id));

            const selectedIds = Object.keys(selection);
            const selectableLogIds = this.filterUnresolvedLogIds(selectedIds);

            this.selectedLogIds = [
                ...idsFromOtherPages,
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
