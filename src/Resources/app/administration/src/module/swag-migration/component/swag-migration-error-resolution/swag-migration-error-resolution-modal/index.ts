import template from './swag-migration-error-resolution-modal.html.twig';
import './swag-migration-error-resolution-modal.scss';
import type { ErrorResolutionTableData } from '../swag-migration-error-resolution-step';
import type { MigrationLog, TRepository } from '../../../../../type/types';
import type { EntityFields, TableColumn } from '../../../service/swag-migration-error-resolution.service';
import { MIGRATION_ERROR_RESOLUTION_SERVICE } from '../../../service/swag-migration-error-resolution.service';

const { Criteria } = Shopware.Data;

/**
 * @private
 */
export interface SwagMigrationErrorResolutionModalData {
    openDetailsModal: boolean;
    tablePage: number;
    tableLimit: number;
    tableTotal: number;
    tableData: Record<string, unknown>[];
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
    },

    methods: {
        async createdComponent() {
            await this.fetchLogs();
        },

        fetchLogs() {
            if (!this.selectedLog) {
                return Promise.resolve();
            }

            this.loading = true;

            const criteria = new Criteria(this.tablePage, this.tableLimit)
                .addFilter(Criteria.equals('code', this.selectedLog.code))
                .addFilter(Criteria.equals('entityName', this.selectedLog.entityName))
                .addFilter(Criteria.equals('fieldName', this.selectedLog.fieldName));

            const entityFieldProperties = this.tableColumns
                .filter((column) => column.property !== 'status')
                .map((column) => column.property);

            return this.migrationLoggingRepository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.tableTotal = result.total;

                    this.tableData = result.map((log: MigrationLog) => {
                        const convertedData = log?.convertedData || {};

                        const row = {
                            status: false,
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
    },
});
