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
                    const parsedValue = this.parseJsonValue();

                    this.updateFieldValue(parsedValue);
                }
            },
            immediate: true,
        },
    },

    methods: {
        formatInitialValue(): string {
            return `{\n  "${this.fieldName}": "",\n}`;
        },

        cleanJsonString(jsonString: string): string {
            // remove trailing commas before closing braces and brackets
            return jsonString.replace(/,(\s*[}\]])/g, '$1').trim();
        },

        parseJsonValue(): string | number | boolean | null | object | unknown[] {
            if (!this.fieldValue || typeof this.fieldValue !== 'string') {
                return this.fieldValue;
            }

            try {
                const cleanedJson = this.cleanJsonString(this.fieldValue);
                const parsed = JSON.parse(cleanedJson);

                if (typeof parsed === 'object' && parsed !== null && this.fieldName in parsed) {
                    return parsed[this.fieldName];
                }

                return parsed;
            } catch {
                return this.fieldValue;
            }
        },
    },
});
