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
            fieldValue: '',
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
        cleanJsonString(jsonString: string): string {
            return jsonString.replace(/,(\s*[}\]])/g, '$1').trim();
        },

        parseJsonFieldValue(): string | number | boolean | null | object | unknown[] {
            if (!this.fieldValue || typeof this.fieldValue !== 'string') {
                return this.fieldValue;
            }

            try {
                const cleanedJson = this.cleanJsonString(this.fieldValue);

                return JSON.parse(cleanedJson);
            } catch {
                return this.fieldValue;
            }
        },
    },
});
