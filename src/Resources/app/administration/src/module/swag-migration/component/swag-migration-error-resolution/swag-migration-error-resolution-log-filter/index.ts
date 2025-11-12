import template from './swag-migration-error-resolution-log-filter.html.twig';
import './swag-migration-error-resolution-log-filter.scss';
import type { ErrorResolutionTableData } from '../swag-migration-error-resolution-step';

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
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    emits: ['log-filter-change'],

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
        };
    },

    computed: {
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
            return this.buildOptionsFromProperty('code');
        },

        entityOptions(): Option[] {
            return this.buildOptionsFromProperty('entityName');
        },

        fieldOptions(): Option[] {
            return this.buildOptionsFromProperty('fieldName');
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

        onTogglePopover() {
            this.open = !this.open;
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
    },
});
