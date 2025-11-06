import template from './swag-migration-error-resolution-field-unhandled.html.twig';

/**
 * @private
 */
export interface SwagMigrationErrorResolutionFieldUnhandledData {
    fieldValue: string;
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
    },

    data(): SwagMigrationErrorResolutionFieldUnhandledData {
        return {
            fieldValue: this.formatInitialValue(),
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

    methods: {
        formatInitialValue(): string {
            return `{\n  "${this.fieldName}": "",\n}`;
        },
    },
});
