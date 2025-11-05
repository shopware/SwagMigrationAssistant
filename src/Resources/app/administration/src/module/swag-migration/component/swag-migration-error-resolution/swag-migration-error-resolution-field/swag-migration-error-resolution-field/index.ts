import type { Property } from '@administration/src/core/data/entity-definition.data';
import template from './swag-migration-error-resolution-field.html.twig';
import './swag-migration-error-resolution-field.scss';
import type { ErrorResolutionTableData } from '../../swag-migration-error-resolution-step';

const DATA_TYPES = {
    UUID: 'uuid',
    INT: 'int',
    TEXT: 'text',
    FLOAT: 'float',
    STRING: 'string',
    BOOLEAN: 'boolean',
    DATE: 'date',
    JSON_LIST: 'json_list',
    JSON_OBJECT: 'json_object',
    ASSOCIATION: 'association',
} as const;

const UNHANDLED_FIELD_TYPES = [
    'blob',
    'password',
] as const;

const UNHANDLED_FIELD_NAMES = [
    'id',
    'autoIncrement',
    'translated',
] as const;

const HANDLED_RELATION_TYPES = {
    MANY_TO_ONE: 'many_to_one',
    ONE_TO_MANY: 'one_to_many',
    MANY_TO_MANY: 'many_to_many',
} as const;

const FIELD_COMPONENT_TYPES = {
    NUMBER: 'number',
    TEXTAREA: 'textarea',
    TEXT: 'text',
    SWITCH: 'switch',
    DATEPICKER: 'datepicker',
    EDITOR: 'editor',
} as const;

const FIELD_TYPE_COMPONENT_MAPPING = {
    [DATA_TYPES.INT]: FIELD_COMPONENT_TYPES.NUMBER,
    [DATA_TYPES.TEXT]: FIELD_COMPONENT_TYPES.TEXTAREA,
    [DATA_TYPES.FLOAT]: FIELD_COMPONENT_TYPES.NUMBER,
    [DATA_TYPES.STRING]: FIELD_COMPONENT_TYPES.TEXT,
    [DATA_TYPES.BOOLEAN]: FIELD_COMPONENT_TYPES.SWITCH,
    [DATA_TYPES.DATE]: FIELD_COMPONENT_TYPES.DATEPICKER,
    [DATA_TYPES.JSON_LIST]: FIELD_COMPONENT_TYPES.EDITOR,
    [DATA_TYPES.JSON_OBJECT]: FIELD_COMPONENT_TYPES.EDITOR,
} as const;

/**
 * @private
 */
export {
    DATA_TYPES,
    UNHANDLED_FIELD_TYPES,
    UNHANDLED_FIELD_NAMES,
    HANDLED_RELATION_TYPES,
    FIELD_COMPONENT_TYPES,
    FIELD_TYPE_COMPONENT_MAPPING,
};

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        log: {
            type: Object as PropType<ErrorResolutionTableData>,
            required: true,
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    computed: {
        entitySchema() {
            if (this.log?.entityName && Shopware.EntityDefinition.has(this.log.entityName)) {
                return Shopware.EntityDefinition.get(this.log.entityName);
            }

            return null;
        },

        entityField(): Property | null {
            if (this.entitySchema && this.log?.fieldName && !UNHANDLED_FIELD_NAMES.includes(this.log.fieldName)) {
                return this.entitySchema.getField(this.log.fieldName) ?? null;
            }

            return null;
        },

        correspondingAssociationField(): Property | null {
            if (!this.entitySchema || !this.entityField || !this.log?.fieldName) {
                return null;
            }

            if (this.entityField.type !== DATA_TYPES.UUID) {
                return null;
            }

            if (this.entityField.flags?.primary_key === true) {
                return null;
            }

            let associationField: Property | null = null;

            this.entitySchema.forEachField((property: Property) => {
                if (associationField) {
                    return;
                }

                if (
                    property.type === DATA_TYPES.ASSOCIATION &&
                    (property as Property & { localField?: string }).localField === this.log.fieldName
                ) {
                    associationField = property;
                }
            });

            return associationField;
        },

        isScalarField() {
            if (!this.entityField) {
                return true;
            }

            if (this.entityField.type === DATA_TYPES.ASSOCIATION) {
                return false;
            }

            if (this.entityField.type === DATA_TYPES.UUID && this.correspondingAssociationField) {
                return false;
            }

            return true;
        },

        effectiveEntityField(): Property | null {
            if (this.correspondingAssociationField) {
                return this.correspondingAssociationField;
            }

            return this.entityField;
        },

        fieldType() {
            if (!this.entityField || UNHANDLED_FIELD_TYPES.includes(this.entityField.type)) {
                return null;
            }

            const isAssociation = this.entityField.type === DATA_TYPES.ASSOCIATION;
            const hasValidRelation =
                this.entityField.relation && Object.values(HANDLED_RELATION_TYPES).includes(this.entityField.relation);

            if (isAssociation && hasValidRelation) {
                return this.entityField.relation;
            }

            if (this.entityField.type === DATA_TYPES.UUID && this.correspondingAssociationField) {
                const associationRelation = this.correspondingAssociationField.relation;
                if (associationRelation && Object.values(HANDLED_RELATION_TYPES).includes(associationRelation)) {
                    return associationRelation;
                }
            }

            if (Object.values(HANDLED_RELATION_TYPES).includes(this.entityField.type)) {
                return this.entityField.type;
            }

            return FIELD_TYPE_COMPONENT_MAPPING[this.entityField.type] ?? null;
        },
    },

    methods: {
        onScalarFieldValueChanged(newValue: string | number | boolean | null) {
            console.log({
                scalarField: newValue,
            });
        },
    },
});
