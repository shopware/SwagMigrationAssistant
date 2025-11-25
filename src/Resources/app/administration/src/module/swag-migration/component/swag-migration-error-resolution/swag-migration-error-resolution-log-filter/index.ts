import type { PropType } from 'vue';
import template from './swag-migration-error-resolution-log-filter.html.twig';
import './swag-migration-error-resolution-log-filter.scss';
import type { ErrorResolutionTableData } from '../swag-migration-error-resolution-step';
import type { TRepository } from '../../../../../type/types';
import type { MigrationStore } from '../../../store/migration.store';
import { MIGRATION_STORE_ID } from '../../../store/migration.store';
import { MIGRATION_ERROR_RESOLUTION_SERVICE } from '../../../service/swag-migration-error-resolution.service';

const { debounce } = Shopware.Utils;
const { Criteria } = Shopware.Data;

/**
 * @private
 */
export const fieldMap = {
    code: 'code',
    entity: 'entityName',
    field: 'fieldName',
} as const;

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
    searchResults: {
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
            required: true,
        },
    },

    data(): SwagMigrationErrorResolutionLogFilterData {
        return {
            loading: false,
            value: this.getInitialFilterValue(),
            migrationStore: Shopware.Store.get(MIGRATION_STORE_ID),
            searchResults: {
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
            return this.searchResults.code;
        },

        entityOptions(): Option[] {
            return this.searchResults.entity;
        },

        fieldOptions(): Option[] {
            return this.searchResults.field;
        },

        filterCount(): number {
            return Object.values(this.value).filter((val) => !!val)?.length;
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

        buildResultsMap(type: keyof LogFilterValue, values: string[]): { value: string; label: string }[] {
            return values.map((value) => {
                if (type === fieldMap.code) {
                    return {
                        value,
                        label: this.swagMigrationErrorResolutionService.translateErrorCode(value),
                    };
                }

                return {
                    value,
                    label: value,
                };
            });
        },

        onSearch({ searchTerm }: { searchTerm: string | null }, type: keyof LogFilterValue): Option[] {
            this.debouncedFetchSearchResults(searchTerm, type);

            const optionsMap = {
                code: this.codeOptions,
                entity: this.entityOptions,
                field: this.fieldOptions,
                status: this.statusOptions,
            };

            return optionsMap[type] ?? [];
        },

        debouncedFetchSearchResults: debounce(async function fetchSearch(
            searchTerm: string | null,
            type: keyof LogFilterValue,
        ) {
            if (!searchTerm || searchTerm.length < 3) {
                await this.loadInitialOptions(type);

                return;
            }

            await this.fetchSearchResults(searchTerm, type);
        }, 400),

        async fetchSearchResults(searchTerm: string, type: keyof LogFilterValue) {
            const field = fieldMap[type];

            const aggregationName = `${type}Aggregation`;

            const criteria = new Criteria(1, 1)
                .addAggregation(Criteria.terms(aggregationName, field, 25, null, null))
                .addFilter(Criteria.equals('runId', this.runId))
                .addFilter(Criteria.equals('userFixable', 1));

            if (searchTerm) {
                criteria.setTerm(searchTerm);
            }

            const result = await this.migrationLoggingRepository.search(criteria);
            const aggregation = result.aggregations?.[aggregationName];

            if (!aggregation || !aggregation.buckets) {
                this.searchResults = {
                    ...this.searchResults,
                    [type]: [],
                };

                return;
            }

            const uniqueValues = aggregation.buckets.map((bucket) => bucket.key);

            this.searchResults = {
                ...this.searchResults,
                [type]: this.buildResultsMap(type, uniqueValues),
            };
        },

        async loadInitialOptions(type: keyof LogFilterValue) {
            const field = fieldMap[type];

            const aggregationName = `${type}Aggregation`;

            const criteria = new Criteria(1, 1)
                .addAggregation(Criteria.terms(`${type}Aggregation`, field, 250, null, null))
                .addFilter(Criteria.equals('runId', this.runId))
                .addFilter(Criteria.equals('userFixable', 1));

            const result = await this.migrationLoggingRepository.search(criteria);
            const aggregation = result.aggregations?.[aggregationName];

            if (aggregation && aggregation.buckets) {
                this.searchResults = {
                    ...this.searchResults,
                    [type]: this.buildResultsMap(
                        type,
                        aggregation.buckets.map((bucket) => bucket.key),
                    ),
                };
            }
        },

        async onTogglePopover(isOpened: boolean, toggleFloatingUi: () => void) {
            if (isOpened) {
                toggleFloatingUi();

                return;
            }

            this.loading = true;

            try {
                await Promise.all([
                    this.loadInitialOptions('code'),
                    this.loadInitialOptions('entity'),
                    this.loadInitialOptions('field'),
                ]);

                toggleFloatingUi();
            } finally {
                this.loading = false;
            }
        },

        onValueChange(newValue: Partial<LogFilterValue>) {
            this.value = {
                ...this.value,
                ...newValue,
            };

            // reload initial options for fields that were cleared
            Object.keys(newValue).forEach((key) => {
                const filterKey = key as keyof LogFilterValue;

                if (newValue[filterKey] === null && filterKey !== 'status') {
                    void this.loadInitialOptions(filterKey);
                }
            });

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
