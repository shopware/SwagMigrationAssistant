import type { Property } from '@administration/src/core/data/entity-definition.data';
import template from './swag-migration-error-resolution-field-relation.html.twig';
import { HANDLED_RELATION_TYPES } from '../swag-migration-error-resolution-field';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'repositoryFactory',
    ],

    props: {
        relationType: {
            type: String,
            required: true,
            validator: (value: string) => {
                return (Object.values(HANDLED_RELATION_TYPES) as string[]).includes(value);
            },
        },
        entityField: {
            type: Object as PropType<Property>,
            required: true,
        },
    },

    computed: {
        entityName() {
            return this.entityField?.entity ?? '';
        },

        entityRepository() {
            if (!this.entityName) {
                return null;
            }

            return this.repositoryFactory.create(this.entityName);
        },
    },
});
