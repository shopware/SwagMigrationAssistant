import template from './swag-migration-error-resolution-field-unhandled.html.twig';

/**
 * @private
 */
export interface SwagMigrationErrorResolutionFieldUnhandledData {
    fieldValue: string;
    error: { detail: string } | null;
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
            fieldValue: '',
            error: null,
        };
    },

    watch: {
        fieldValue: {
            handler() {
                if (this.updateFieldValue) {
                    const parsedValue = this.parseJsonFieldValue();

                    this.updateFieldValue(parsedValue);
                }
            },
            immediate: true,
        },
    },

    methods: {
        parseJsonFieldValue(): string | number | boolean | null | object | unknown[] {
            if (!this.fieldValue || typeof this.fieldValue !== 'string') {
                this.error = null;

                return this.fieldValue;
            }

            try {
                const value = JSON.parse(this.fieldValue);
                this.error = null;

                return value;
            } catch {
                this.error = {
                    detail: this.$tc('swag-migration.index.error-resolution.errors.invalidJsonInput'),
                };

                return null;
            }
        },
    },
});
