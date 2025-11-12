import template from './swag-migration-error-resolution-log-filter.html.twig';
import './swag-migration-error-resolution-log-filter.scss';
import type { ErrorResolutionTableData } from '../swag-migration-error-resolution-step';
import type { TRepository } from '../../../../../type/types';
import type { MigrationStore } from '../../../store/migration.store';
import { MIGRATION_STORE_ID } from '../../../store/migration.store';

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
    open: boolean;
    value: LogFilterValue;
    migrationStore: MigrationStore;
    searchResults: {
        code: Option[] | null;
        entity: Option[] | null;
        field: Option[] | null;
    };
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    emits: ['log-filter-change'],

    inject: ['repositoryFactory'],

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
    },

    data(): SwagMigrationErrorResolutionLogFilterData {
        return {
            open: false,
            value: this.getInitialFilterValue(),
            migrationStore: Shopware.Store.get(MIGRATION_STORE_ID),
            searchResults: {
                code: null,
                entity: null,
                field: null,
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
            return this.searchResults.code ?? this.buildOptionsFromProperty('code');
        },

        entityOptions(): Option[] {
            return this.searchResults.entity ?? this.buildOptionsFromProperty('entityName');
        },

        fieldOptions(): Option[] {
            return this.searchResults.field ?? this.buildOptionsFromProperty('fieldName');
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

        extractUniqueValuesFromTableData(property: keyof ErrorResolutionTableData): string[] {
            const values: string[] = this.tableData
                .map((item) => item[property])
                .filter((value): value is string => Boolean(value) && typeof value === 'string');

            return [
                ...new Set(values),
            ];
        },

        buildOptionsFromProperty(property: keyof ErrorResolutionTableData): Option[] {
            const uniqueValues = this.extractUniqueValuesFromTableData(property);

            return uniqueValues.map((value) => ({
                value,
                label: value,
            }));
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
            if (!searchTerm || searchTerm.length < 2) {
                // this prevents the selected value from disappearing after selection
                if (!this.value[type]) {
                    this.searchResults = {
                        ...this.searchResults,
                        [type]: null,
                    };
                }

                return;
            }

            await this.fetchSearchResults(searchTerm, type);
        }, 400),

        async fetchSearchResults(searchTerm: string, type: keyof LogFilterValue) {
            const field = fieldMap[type] ?? null;

            if (!field) {
                return;
            }

            const criteria = new Criteria(1, 5)
                .setTerm(searchTerm)
                .addFilter(Criteria.equals('userFixable', 1))
                .addIncludes({
                    swag_migration_logging: [
                        'code',
                        'entityName',
                        'fieldName',
                    ],
                });

            const result = await this.migrationLoggingRepository.search(criteria);

            const uniqueValues: string[] = Array.from(
                new Set(
                    result
                        .map((item) => item[field])
                        .filter((value): value is string => Boolean(value) && typeof value === 'string'),
                ),
            );

            this.searchResults = {
                ...this.searchResults,
                [type]: uniqueValues.map((value) => ({
                    value,
                    label: value,
                })),
            };
        },

        onTogglePopover() {
            this.open = !this.open;
        },

        onValueChange(newValue: Partial<LogFilterValue>) {
            this.value = {
                ...this.value,
                ...newValue,
            };

            // reset search results for fields that were cleared
            Object.keys(newValue).forEach((key) => {
                const filterKey = key as keyof LogFilterValue;

                if (newValue[filterKey] === null && filterKey !== 'status') {
                    this.searchResults = {
                        ...this.searchResults,
                        [filterKey]: null,
                    };
                }
            });

            this.$emit('log-filter-change', this.value);
        },

        onReset() {
            this.onValueChange(this.getInitialFilterValue());
            this.searchResults = {
                code: null,
                entity: null,
                field: null,
            };
        },
    },
});
