import template from './swag-migration-error-resolution-field-scalar.html.twig';
import { FIELD_COMPONENT_TYPES } from '../swag-migration-error-resolution-field';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        componentType: {
            type: String,
            required: true,
            validator: (value: string) => {
                return (Object.values(FIELD_COMPONENT_TYPES) as string[]).includes(value);
            },
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
    },
});
