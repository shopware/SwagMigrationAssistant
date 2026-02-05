import template from './swag-migration-error-resolution-field-unhandled.html.twig';

/**
 * @private
 */
export interface SwagMigrationErrorResolutionFieldUnhandledData {
    fieldValue: string | null;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: ['updateFieldValue'],

    props: {
        fieldName: {
            type: String,
            required: true,
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
        error: {
            type: Object as PropType<{ detail: string }>,
            required: false,
            default: null,
        },
        exampleValue: {
            type: String as PropType<string | null>,
            required: false,
            default: null,
        },
    },

    data(): SwagMigrationErrorResolutionFieldUnhandledData {
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
        exampleValue: {
            handler(newValue: string | null) {
                if (newValue !== null && this.fieldValue === null) {
                    this.fieldValue = newValue;
                }
            },
            immediate: true,
        },
    },
});
