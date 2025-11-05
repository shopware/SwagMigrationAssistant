import type { Property } from '@administration/src/core/data/entity-definition.data';
import template from './swag-migration-error-resolution-field-relation.html.twig';
import {
    HANDLED_RELATION_TYPES,
    MIGRATION_ERROR_RESOLUTION_SERVICE,
} from '../../../../service/swag-migration-error-resolution.service';
import './swag-migration-error-resolution-field-relation.scss';

export interface SwagMigrationErrorResolutionFieldRelationData {
    fieldValue: string | string[] | null;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    emits: ['relation-field-value-changed'],

    inject: [
        'repositoryFactory',
        MIGRATION_ERROR_RESOLUTION_SERVICE,
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

    data(): SwagMigrationErrorResolutionFieldRelationData {
        return {
            fieldValue: this.isToOneRelation ? null : [],
        };
    },

    watch: {
        fieldValue() {
            this.$emit('relation-field-value-changed', this.fieldValue);
        },
    },

    computed: {
        isToOneRelation(): boolean {
            return this.relationType === HANDLED_RELATION_TYPES.MANY_TO_ONE;
        },

        isToManyRelation(): boolean {
            return (
                this.relationType === HANDLED_RELATION_TYPES.ONE_TO_MANY ||
                this.relationType === HANDLED_RELATION_TYPES.MANY_TO_MANY
            );
        },

        entityName(): string {
            return this.entityField?.entity ?? '';
        },

        entityRepository() {
            if (!this.entityName) {
                return null;
            }

            return this.repositoryFactory.create(this.entityName);
        },

        labelProperty(): string | null {
            return this.swagMigrationErrorResolutionService.getHighestPriorityFieldName(this.entityName);
        },
    },

    methods: {
        getLabelValue(item: Record<string, unknown>): string {
            if (!this.labelProperty || !item) {
                return '';
            }

            const value = item[this.labelProperty];

            if (value === null || value === undefined) {
                return '';
            }

            return String(value);
        },
    },
});
