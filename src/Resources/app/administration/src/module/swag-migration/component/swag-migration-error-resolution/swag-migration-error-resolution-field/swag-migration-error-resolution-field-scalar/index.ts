import type { Property } from '@administration/src/core/data/entity-definition.data';
import template from './swag-migration-error-resolution-field-scalar.html.twig';
import { FIELD_COMPONENT_TYPES } from '../../../../service/swag-migration-error-resolution.service';

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

    inject: [
        'updateFieldValue',
    ],

    props: {
        componentType: {
            type: String,
            required: true,
            validator: (value: string) => {
                return (Object.values(FIELD_COMPONENT_TYPES) as string[]).includes(value);
            },
        },
        entityField: {
            type: Object as PropType<Property>,
            required: true,
        },
        fieldName: {
            type: String,
            required: true,
        },
        error: {
            type: Object as PropType<{ detail: string }>,
            required: false,
            default: null,
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
        exampleValue: {
            type: String as PropType<string | null>,
            required: false,
            default: null,
        },
    },

    data(): SwagMigrationErrorResolutionFieldScalarData {
        return {
            fieldValue: null,
        };
    },

    watch: {
        fieldValue: {
            handler() {
                if (this.componentType === 'switch' && this.fieldValue === null) {
                    this.fieldValue = false;
                }

                if (this.updateFieldValue) {
                    this.updateFieldValue(this.fieldValue);
                }
            },
            immediate: true,
        },
        exampleValue: {
            handler(newValue: string | null) {
                if (this.componentType === 'editor' && newValue !== null && this.fieldValue === null) {
                    this.fieldValue = newValue;
                }
            },
            immediate: true,
        },
    },

    computed: {
        numberFieldType(): string {
            return this.entityField?.type || 'int';
        },
    },
});
