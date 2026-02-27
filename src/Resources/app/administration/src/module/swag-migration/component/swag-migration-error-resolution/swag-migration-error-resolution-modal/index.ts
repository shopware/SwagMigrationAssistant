import template from './swag-migration-error-resolution-modal.html.twig';
import './swag-migration-error-resolution-modal.scss';
import type { ErrorResolutionTableData } from '../swag-migration-error-resolution-step';
import type { MigrationFix, MigrationLog, TRepository } from '../../../../../type/types';
import type { TableColumn } from '../../../service/swag-migration-error-resolution.service';
import { MIGRATION_ERROR_RESOLUTION_SERVICE } from '../../../service/swag-migration-error-resolution.service';
import { MIGRATION_API_SERVICE } from '../../../../../core/service/api/swag-migration.api.service';
import { MIGRATION_STORE_ID, type MigrationStore } from '../../../store/migration.store';

const { Criteria } = Shopware.Data;

/**
 * @private
 *
 * Determines which component resolves a specific error code.
 * 'DEFAULT' means the default component renders a matching input field.
 * null will render an unresolvable message.
 */
export const ERROR_CODE_COMPONENT_MAPPING: Record<string, string> = {
    SWAG_MIGRATION_VALIDATION_OPTIONAL_FIELD_VALUE_INVALID: 'DEFAULT',
    SWAG_MIGRATION_VALIDATION_REQUIRED_FIELD_VALUE_INVALID: 'DEFAULT',
    SWAG_MIGRATION_VALIDATION_REQUIRED_FIELD_MISSING: 'DEFAULT',
} as const;

/**
 * @private
 */
export type ResolutionModalRow = {
    status: boolean;
    entityId?: string;
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
    selectAllMode: boolean;
    selectedDetailsLog: ResolutionModalRow;
    loading: boolean;
    submitLoading: boolean;
    fieldValue: string[] | string | boolean | number | null;
    fieldError: { detail: string } | null;
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
        runId: {
            type: String,
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
            selectedDetailsLog: null,
            loading: false,
            submitLoading: false,
            fieldValue: null,
            fieldError: null,
            migrationStore: Shopware.Store.get(MIGRATION_STORE_ID),
            selectedLogIds: [],
            selectAllMode: false,
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
                .addFilter(Criteria.equals('runId', this.runId))
                .addFilter(Criteria.equals('code', this.selectedLog.code))
                .addFilter(Criteria.equals('entityName', this.selectedLog.entityName))
                .addFilter(Criteria.equals('fieldName', this.selectedLog.fieldName))
                .addIncludes({
                    swag_migration_logging: [
                        'id',
                        'entityId',
                        'convertedData',
                        'sourceData',
                    ],
                });
        },

        modalTitle() {
            const translatedCode = this.swagMigrationErrorResolutionService.translateErrorCode(this.selectedLog.code);

            return this.$tc('swag-migration.index.error-resolution.modals.error.title', {
                code: translatedCode,
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

            if (this.selectAllMode) {
                this.tableData.forEach((row) => {
                    if (!row.status) {
                        selection[row.logId] = row;
                    }
                });

                return selection;
            }

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

        resolvingComponent(): string | null {
            return ERROR_CODE_COMPONENT_MAPPING[this.selectedLog.code] || null;
        },

        isResolutionDisabled(): boolean {
            return this.loading || (this.selectedLogIds.length === 0 && !this.selectAllMode);
        },

        selectedCount(): number {
            if (this.selectAllMode) {
                return this.tableTotal - this.selectedLog.fixCount;
            }

            return this.selectedLogIds.length;
        },
    },

    methods: {
        async createdComponent() {
            await this.fetchLogs();
        },

        async onSubmitResolution() {
            this.submitLoading = true;
            this.fieldError = null;

            if (!(await this.validateResolution())) {
                this.submitLoading = false;
                return;
            }

            try {
                if (this.selectAllMode) {
                    await this.submitResolutionInBatches();
                } else {
                    await this.submitResolutionForSelectedIds();
                }

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

        async validateResolution(): Promise<boolean> {
            const validationError = this.swagMigrationErrorResolutionService.validateFieldValue(
                this.selectedLog.entityName,
                this.selectedLog.fieldName,
                this.fieldValue,
            );

            if (validationError) {
                this.createNotificationError({
                    message: this.$tc(`swag-migration.index.error-resolution.errors.${validationError}`),
                });

                return false;
            }

            const serializationError = await this.migrationApiService
                .validateResolution(this.selectedLog.entityName, this.selectedLog.fieldName, this.fieldValue)
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc('swag-migration.index.error-resolution.errors.validationFailed'),
                    });
                    return null;
                });

            if (!serializationError) {
                return false;
            }

            if (serializationError.valid === true) {
                return true;
            }

            const message = serializationError.violations?.at(0)?.message;

            if (!message) {
                return false;
            }

            this.fieldError = { detail: message };

            return false;
        },

        async submitResolutionForSelectedIds() {
            const entityIds = await this.collectEntityIdsForSubmission();

            if (entityIds.length === 0) {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.noEntityIdsFound'),
                });

                return;
            }

            const entities = entityIds.map((entityId) => this.createResolutionEntity(entityId));

            this.selectedLog.fixCount += entities.length;

            await this.migrationFixRepository.saveAll(entities);
        },

        async submitResolutionInBatches() {
            const { count, limit } = await this.migrationApiService.getUnresolvedLogsBatchInformation(
                this.runId,
                this.selectedLog.code,
                this.selectedLog.entityName,
                this.selectedLog.fieldName,
                this.migrationStore.connectionId,
            );

            const iterations = Math.ceil(count / limit);

            for (let i = 0; i < iterations; i += 1) {
                // each batch must be completed before fetching the next
                // eslint-disable-next-line no-await-in-loop
                const { entityIds } = await this.migrationApiService.getLogEntityIdsWithoutFix(
                    this.runId,
                    this.selectedLog.code,
                    this.selectedLog.entityName,
                    this.selectedLog.fieldName,
                    limit,
                    this.migrationStore.connectionId,
                );

                const entities = entityIds.map((entityId: string) => this.createResolutionEntity(entityId));

                // each batch must be completed before fetching the next
                // eslint-disable-next-line no-await-in-loop
                await this.migrationFixRepository.saveAll(entities);
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
                .map((row) => row.entityId)
                .filter((id: string | null): id is string => id !== null);
        },

        getMissingLogIds(): string[] {
            const currentPageLogIds = new Set(this.tableData.map((row) => row.logId));

            return this.selectedLogIds.filter((logId) => !currentPageLogIds.has(logId));
        },

        async fetchEntityIdsFromMissingLogs(missingLogIds: string[]): Promise<string[]> {
            const criteria = new Criteria(1, missingLogIds.length)
                .addIncludes({ swag_migration_logging: ['entityId'] })
                .setIds(missingLogIds);

            const logs = await this.migrationLoggingRepository.search(criteria);

            return logs
                .map((log: MigrationLog) => log.entityId)
                .filter((id: string | null | undefined): id is string => id !== null && id !== undefined);
        },

        resetSelection() {
            this.selectedLogIds = [];
            this.selectAllMode = false;

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

            entity.value = this.swagMigrationErrorResolutionService.normalizeFieldValueForSave(this.fieldValue);

            return entity;
        },

        async fetchLogs(): Promise<void> {
            this.loading = true;

            try {
                const logsResult = await this.migrationLoggingRepository.search(this.loggingCriteria);
                this.tableTotal = logsResult.total;

                const entityIds = this.extractEntityIdsFromLogs(logsResult);

                const fixesMap = await this.buildFixesMap(entityIds);
                const entityFieldProperties = this.getEntityFieldProperties();

                this.tableData = logsResult.map((log: MigrationLog) =>
                    this.mapLogToTableRow(log, fixesMap, entityFieldProperties),
                );
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
                .map((log: MigrationLog) => log.entityId)
                .filter((id: string | null | undefined): id is string => id !== null && id !== undefined);
        },

        async buildFixesMap(entityIds: string[]): Promise<Map<string, MigrationFix>> {
            const existingFixes = await this.fetchExistingFixesForEntityIds(entityIds);

            return new Map(
                existingFixes.map((fix) => [
                    fix.entityId,
                    fix,
                ]),
            );
        },

        mapLogToTableRow(
            log: MigrationLog,
            fixesMap: Map<string, MigrationFix>,
            entityFieldProperties: string[],
        ): ResolutionModalRow {
            const convertedData = log?.convertedData || {};

            const fix = log.entityId ? fixesMap.get(log.entityId) : undefined;
            const hasFixApplied = fix?.value !== undefined;

            const row: ResolutionModalRow = {
                logId: log.id,
                fixId: fix?.id,
                entityId: log.entityId,
                status: hasFixApplied,
                convertedData,
                sourceData: log?.sourceData || {},
                ...this.swagMigrationErrorResolutionService.mapEntityFieldProperties(
                    this.selectedLog.entityName,
                    entityFieldProperties,
                    convertedData,
                ),
            };

            if (hasFixApplied) {
                Shopware.Utils.object.set(row, this.selectedLog.fieldName, fix.value);
            }

            return row;
        },

        filterUnresolvedLogIds(logIds: string[]): string[] {
            const unresolvedRows = new Set(this.tableData.filter((row) => !row.status).map((row) => row.logId));

            return logIds.filter((logId) => unresolvedRows.has(logId));
        },

        async fetchExistingFixesForEntityIds(entityIds: string[]): Promise<MigrationFix[]> {
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
                            'id',
                            'entityId',
                            'value',
                        ],
                    });

                return [...(await this.migrationFixRepository.search(criteria))];
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.fetchExistingFixesFailed'),
                });

                return [];
            }
        },

        async onSelectAllLogs() {
            if (!this.selectedLog || this.selectAllMode) {
                return;
            }

            if (!this.selectAllMode) {
                // force select-all behaviour
                this.applySelectionToGrid(true);

                await this.$nextTick();

                this.selectedLogIds = [];
                this.selectAllMode = true;
            }
        },

        async onResetResolution(item: { fixId: string }) {
            this.loading = true;

            try {
                await this.migrationFixRepository.delete(item.fixId);

                await this.fetchLogs();
                this.resetSelection();

                this.selectedLog.fixCount -= 1;

                this.$emit('fixes-reset');
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.resetResolutionFailed'),
                });
            } finally {
                this.loading = false;
            }
        },

        applySelectionToGrid(forceSelectAll = false) {
            const gridRef = this.$refs.errorResolutionGrid;

            if (forceSelectAll) {
                this.tableData.forEach((row) => {
                    if (!row.status) {
                        gridRef.selectItem(true, row);
                    }
                });

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
                ? 'swag-migration-error-resolution-modal__left-status--resolved'
                : 'swag-migration-error-resolution-modal__left-status--unresolved';
        },

        statusBadgeText(isResolved: boolean): string {
            return isResolved
                ? this.$tc('swag-migration.index.error-resolution.modals.error.left.status.resolved')
                : this.$tc('swag-migration.index.error-resolution.modals.error.left.status.unresolved');
        },

        onSelectionChanged(selection: Record<string, ResolutionModalRow>) {
            if (!selection || Object.keys(selection).length === 0) {
                this.selectAllMode = false;
                this.selectedLogIds = [];

                return;
            }

            const currentPageIds = new Set(this.tableData.map((row) => row.logId));
            const idsFromOtherPages = this.selectedLogIds.filter((id) => !currentPageIds.has(id));

            const selectedIds = Object.keys(selection);
            const selectableLogIds = this.filterUnresolvedLogIds(selectedIds);

            this.selectedLogIds = [
                ...idsFromOtherPages,
                ...selectableLogIds,
            ];
        },

        isRecordSelectable(item: ResolutionModalRow): boolean {
            return !this.selectAllMode && !item.status;
        },

        onOpenDetailsModal(row: ResolutionModalRow) {
            this.selectedDetailsLog = row;
            this.openDetailsModal = true;
        },

        onCloseDetailsModal() {
            this.openDetailsModal = false;
            this.selectedDetailsLog = null;
        },

        async onPageChange({ page, limit }: { page: number; limit: number }) {
            this.tablePage = page;
            this.tableLimit = limit;

            // temporarily disable select all mode to allow select checkboxes before disabling them
            const wasSelectAllMode = this.selectAllMode;
            this.selectAllMode = false;

            await this.fetchLogs();
            await this.$nextTick();

            this.applySelectionToGrid(wasSelectAllMode);

            if (wasSelectAllMode) {
                await this.$nextTick();
                this.selectAllMode = true;
            }
        },
    },
});
