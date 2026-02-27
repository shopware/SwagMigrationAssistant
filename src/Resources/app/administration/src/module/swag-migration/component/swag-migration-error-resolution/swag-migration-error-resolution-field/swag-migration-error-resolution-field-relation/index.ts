import type { Property } from '@administration/src/core/data/entity-definition.data';
import template from './swag-migration-error-resolution-field-relation.html.twig';
import {
    HANDLED_RELATION_TYPES,
    MIGRATION_ERROR_RESOLUTION_SERVICE,
} from '../../../../service/swag-migration-error-resolution.service';
import './swag-migration-error-resolution-field-relation.scss';

const { Criteria } = Shopware.Data;

/**
 * @private
 */
export interface SwagMigrationErrorResolutionFieldRelationData {
    fieldValue: string | string[] | null;
    noOptionsFound: boolean;
    entityLink: { name: string } | null;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'repositoryFactory',
        MIGRATION_ERROR_RESOLUTION_SERVICE,
        'updateFieldValue',
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
            // don't use `isToOneRelation` here, because data() is called before computed properties are set up
            fieldValue: this.relationType === HANDLED_RELATION_TYPES.MANY_TO_ONE ? null : [],
            noOptionsFound: false,
            entityLink: null,
        };
    },

    created() {
        this.checkEntityAvailability();
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
        async checkEntityAvailability() {
            if (!this.entityRepository) {
                return;
            }

            const criteria = new Criteria(1, 1).addIncludes({
                [this.entityName]: ['id'],
            });

            const result = await this.entityRepository.search(criteria);

            if (result.total === 0) {
                this.noOptionsFound = true;
                this.entityLink = this.swagMigrationErrorResolutionService.getEntityLink(this.entityName);
            }
        },

        getLabelValue(item: Record<string, unknown>): string {
            if (!this.labelProperty) {
                return '';
            }

            const value = item[this.labelProperty];

            if (!value) {
                return '';
            }

            return String(value);
        },
    },
});
