import template from './swag-migration-dynamic-resolution-field-scalar.html.twig';
import { FIELD_COMPONENT_TYPES } from '../swag-migration-dynamic-resolution-field';

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
    },
});
