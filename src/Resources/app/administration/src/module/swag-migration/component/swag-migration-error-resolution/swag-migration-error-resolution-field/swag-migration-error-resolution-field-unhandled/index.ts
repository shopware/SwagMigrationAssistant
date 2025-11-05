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

    emits: ['unhandled-field-value-changed'],

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
        fieldValue() {
            this.$emit('unhandled-field-value-changed', this.fieldValue);
        },
    },

    methods: {
        formatInitialValue(): string {
            return `{\n  "${this.fieldName}": "",\n}`;
        },
    },
});
