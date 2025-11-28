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

    inject: ['updateFieldValue'],

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

    watch: {
        fieldValue: {
            handler() {
                if (this.updateFieldValue) {
                    this.updateFieldValue(this.fieldValue);
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
