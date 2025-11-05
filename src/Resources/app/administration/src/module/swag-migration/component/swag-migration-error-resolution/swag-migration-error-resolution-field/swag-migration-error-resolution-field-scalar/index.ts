import template from './swag-migration-error-resolution-field-scalar.html.twig';
import { FIELD_COMPONENT_TYPES } from '../swag-migration-error-resolution-field';

/**
 * @private
 */
export interface SwagMigrationErrorResolutionFieldScalarData {
    fieldValue: string | number | boolean | null;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    emits: ['scalar-field-value-changed'],

    props: {
        componentType: {
            type: String,
            required: true,
            validator: (value: string) => {
                return (Object.values(FIELD_COMPONENT_TYPES) as string[]).includes(value);
            },
        },
        entityField: {
            type: Object,
            required: true,
        },
        fieldName: {
            type: String,
            required: true,
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    data(): SwagMigrationErrorResolutionFieldScalarData {
        return {
            fieldValue: null,
        };
    },

    computed: {
        numberFieldType(): string | null {
            if (this.componentType !== FIELD_COMPONENT_TYPES.NUMBER) {
                return null;
            }

            return this.entityField?.type || 'int';
        },
    },

    watch: {
        fieldValue() {
            this.$emit('scalar-field-value-changed', this.fieldValue);
        },
    },
});
