import type { PropType } from 'vue';
import template from './swag-migration-error-resolution-log-filter.html.twig';
import './swag-migration-error-resolution-log-filter.scss';
import type { ErrorResolutionTableData } from '../swag-migration-error-resolution-step';
import type { TRepository } from '../../../../../type/types';
import type { MigrationStore } from '../../../store/migration.store';
import { MIGRATION_STORE_ID } from '../../../store/migration.store';
import { MIGRATION_ERROR_RESOLUTION_SERVICE } from '../../../service/swag-migration-error-resolution.service';

const { Criteria } = Shopware.Data;

type Option = {
    value: string;
    label: string;
};

/**
 * @private
 */
export type LogFilterValue = {
    code: string | null;
    status: 'resolved' | 'unresolved' | null;
    entity: string | null;
    field: string | null;
};

/**
 * @private
 */
export interface SwagMigrationErrorResolutionLogFilterData {
    loading: boolean;
    value: LogFilterValue;
    migrationStore: MigrationStore;
    filterOptions: {
        code: Option[];
        entity: Option[];
        field: Option[];
    };
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    emits: ['log-filter-change'],

    inject: [
        MIGRATION_ERROR_RESOLUTION_SERVICE,
        'repositoryFactory',
    ],

    props: {
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
        tableData: {
            type: Array as PropType<ErrorResolutionTableData[]>,
            required: true,
            default: () => [],
        },
        runId: {
            type: String,
            required: false,
            default: null,
        },
    },

    data(): SwagMigrationErrorResolutionLogFilterData {
        return {
            loading: false,
            value: this.getInitialFilterValue(),
            migrationStore: Shopware.Store.get(MIGRATION_STORE_ID),
            filterOptions: {
                code: [],
                entity: [],
                field: [],
            },
        };
    },

    computed: {
        migrationLoggingRepository(): TRepository<'swag_migration_logging'> {
            return this.repositoryFactory.create('swag_migration_logging');
        },

        statusOptions(): Option[] {
            return [
                {
                    value: 'resolved',
                    label: this.$tc('swag-migration.index.error-resolution.step.card.filter.status.options.resolved'),
                },
                {
                    value: 'unresolved',
                    label: this.$tc('swag-migration.index.error-resolution.step.card.filter.status.options.unresolved'),
                },
            ];
        },

        codeOptions(): Option[] {
            return this.filterOptions.code;
        },

        entityOptions(): Option[] {
            return this.filterOptions.entity;
        },

        fieldOptions(): Option[] {
            return this.filterOptions.field;
        },

        filterCount(): number {
            return Object.values(this.value).filter((val) => !!val)?.length;
        },
    },

    watch: {
        runId: {
            immediate: true,
            async handler(newRunId: string | null) {
                if (newRunId) {
                    await this.fetchFilterOptions();
                }
            },
        },
    },

    methods: {
        getInitialFilterValue(): LogFilterValue {
            return {
                code: null,
                status: null,
                entity: null,
                field: null,
            };
        },

        async fetchFilterOptions() {
            if (!this.runId) {
                return;
            }

            this.loading = true;

            const criteria = new Criteria(1, 1)
                .addAggregation(Criteria.terms('codeAggregation', 'code'))
                .addAggregation(Criteria.terms('entityAggregation', 'entityName'))
                .addAggregation(Criteria.terms('fieldAggregation', 'fieldName'))
                .addFilter(Criteria.equals('runId', this.runId))
                .addFilter(Criteria.equals('userFixable', 1));

            try {
                const result = await this.migrationLoggingRepository.search(criteria);

                const { aggregations } = result;

                this.filterOptions = {
                    code: this.buildOptions('code', aggregations?.codeAggregation?.buckets ?? []),
                    entity: this.buildOptions('entity', aggregations?.entityAggregation?.buckets ?? []),
                    field: this.buildOptions('field', aggregations?.fieldAggregation?.buckets ?? []),
                };
            } finally {
                this.loading = false;
            }
        },

        buildOptions(type: string, buckets: Array<{ key: string }>): Option[] {
            return buckets.map((bucket) => {
                if (type === 'code') {
                    return {
                        value: bucket.key,
                        label: this.swagMigrationErrorResolutionService.translateErrorCode(bucket.key),
                    };
                }

                return {
                    value: bucket.key,
                    label: bucket.key,
                };
            });
        },

        onValueChange(newValue: Partial<LogFilterValue>) {
            this.value = {
                ...this.value,
                ...newValue,
            };

            this.$emit('log-filter-change', this.value);
        },

        onReset() {
            this.onValueChange(this.getInitialFilterValue());
        },

        onPopoverOpenChange(isOpened: boolean) {
            if (isOpened) {
                document.addEventListener('pointerdown', this.globalPointerDownHandler, true);

                return;
            }

            document.removeEventListener('pointerdown', this.globalPointerDownHandler, true);
        },

        globalPointerDownHandler(event: PointerEvent) {
            const target = event.target as HTMLElement;

            if (target.closest('.mt-select-result-list-popover-wrapper') !== null) {
                event.stopPropagation();
            }
        },
    },
});
