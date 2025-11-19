import type { Property } from '@administration/src/core/data/entity-definition.data';
import type EntityDefinition from '@administration/src/core/data/entity-definition.data';

/**
 * @private
 */
export interface EntityFields {
    scalar: Record<string, Property>;
    associations: Record<string, Property>;
    required: Record<string, Property>;
}

/**
 * @private
 */
export interface TableColumn {
    label: string;
    property: string;
    sortable: boolean;
    position: number;
    visible?: boolean;
}

/**
 * @private
 */
export const DATA_TYPES = {
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

/**
 * @private
 */
export const UNHANDLED_FIELD_TYPES = [
    'blob',
    'password',
] as const;

/**
 * @private
 */
export const UNHANDLED_FIELD_NAMES = [
    'id',
    'autoIncrement',
    'translated',
] as const;

/**
 * @private
 */
export const HANDLED_RELATION_TYPES = {
    MANY_TO_ONE: 'many_to_one',
    ONE_TO_MANY: 'one_to_many',
    MANY_TO_MANY: 'many_to_many',
} as const;

/**
 * @private
 */
export const FIELD_COMPONENT_TYPES = {
    NUMBER: 'number',
    TEXTAREA: 'textarea',
    TEXT: 'text',
    SWITCH: 'switch',
    DATEPICKER: 'datepicker',
    EDITOR: 'editor',
} as const;

/**
 * @private
 */
export const FIELD_TYPE_COMPONENT_MAPPING = {
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
 * List of fields prioritized for sorting purposes, to determined most meaningful fields first.
 */
export const PRIORITY_FIELDS = [
    'id',
    'name',
    'number',
    'productNumber',
    'orderNumber',
    'customerNumber',
    'technicalName',
    'code',
    'active',
    'visible',
    'status',
    'type',
    'available',
    'createdAt',
    'orderDateTime',
    'releaseDate',
    'birthday',
    'description',
    'title',
    'label',
    'metaTitle',
    'metaDescription',
    'keywords',
    'email',
    'firstName',
    'lastName',
    'alt',
    'url',
    'company',
    'phone',
    'street',
    'city',
    'zipcode',
    'country',
    'manufacturerNumber',
    'ean',
    'iso',
    'iso3',
    'price',
    'amountTotal',
    'amountNet',
    'sales',
    'position',
    'level',
    'path',
    'weight',
    'width',
    'height',
    'length',
] as const;

const PRIORITY_FIELD_MAP: Map<string, number> = new Map(
    PRIORITY_FIELDS.map((field, index) => [
        field,
        index,
    ]),
);

/**
 * @private
 */
export const createEmptyEntityFields = (): EntityFields => ({
    scalar: {},
    associations: {},
    required: {},
});

/**
 * @private
 */
export const CONTENT_TEXT_MAX_LENGTH = 100;

/**
 * @private
 */
export const MIGRATION_ERROR_RESOLUTION_SERVICE = 'swagMigrationErrorResolutionService';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default class SwagMigrationErrorResolutionService {
    /**
     * gets the admin link for a given entity name.
     * tries to find the route by looking up modules registered for the entity.
     */
    getEntityLink(entityName: string | null | undefined): { name: string } | null {
        if (!entityName) {
            return null;
        }

        const findIndexRoute = (module: { routes: Map<string, { routeKey?: string; name?: string }> }): string | null => {
            const indexRoute = Array.from(module.routes.values()).find((route) => {
                return route.routeKey === 'index';
            });

            return indexRoute?.name ?? null;
        };

        const module = Shopware.Module.getModuleByEntityName(entityName);

        if (module) {
            const routeName = findIndexRoute(module);

            if (routeName) {
                return {
                    name: routeName,
                };
            }
        }

        if (entityName.endsWith('_translation')) {
            const baseEntityName = entityName.slice(0, -12);
            const translationModule = Shopware.Module.getModuleByEntityName(baseEntityName);

            if (translationModule) {
                const routeName = findIndexRoute(translationModule);

                if (routeName) {
                    return {
                        name: routeName,
                    };
                }
            }
        }

        return null;
    }

    /**
     * extracts the fields of an entity definition into categorized groups.
     * grouped by scalar fields, associations, and required fields.
     */
    extractEntityFields(entityName: string | null | undefined): EntityFields {
        if (!entityName) {
            return createEmptyEntityFields();
        }

        if (!Shopware.EntityDefinition.has(entityName)) {
            return createEmptyEntityFields();
        }

        const definition = Shopware.EntityDefinition.get(entityName);
        const fields = createEmptyEntityFields();

        definition.forEachField((property: Property, propertyName: string) => {
            if (definition.isScalarField(property)) {
                fields.scalar[propertyName] = property;
            }

            if (property.type === 'association' && definition.isToOneAssociation(property)) {
                fields.associations[propertyName] = property;
            }

            if (property.flags?.required) {
                fields.required[propertyName] = property;
            }
        });

        return fields;
    }

    /**
     * gets the entity schema for a given entity name.
     */
    getEntitySchema(entityName: string | null | undefined): EntityDefinition<never> {
        if (entityName && Shopware.EntityDefinition.has(entityName)) {
            return Shopware.EntityDefinition.get(entityName);
        }

        return null;
    }

    /**
     * gets the entity field definition for a specific field.
     */
    getEntityField(entityName: string | null | undefined, fieldName: string | null | undefined): Property | null {
        if (!fieldName || UNHANDLED_FIELD_NAMES.includes(fieldName as (typeof UNHANDLED_FIELD_NAMES)[number])) {
            return null;
        }

        const schema = this.getEntitySchema(entityName);

        if (!schema) {
            return null;
        }

        return schema.getField(fieldName) ?? null;
    }

    /**
     * finds the corresponding association field for a id field.
     * for example: "productVersionId", "productId" => "product" association.
     */
    findCorrespondingAssociationField(
        entityName: string | null | undefined,
        fieldName: string | null | undefined,
    ): Property | null {
        const schema = this.getEntitySchema(entityName);
        const entityField = this.getEntityField(entityName, fieldName);

        if (!schema || !entityField || !fieldName) {
            return null;
        }

        // only id fields can have corresponding association fields
        if (entityField.type !== DATA_TYPES.UUID) {
            return null;
        }

        // primary key fields do not have corresponding association fields
        if (entityField.flags?.primary_key === true) {
            return null;
        }

        let associationField: Property | null = null;

        // try to find association field by checking all fields for matching localField
        schema.forEachField((property: Property) => {
            if (associationField) {
                return;
            }

            if (
                property.type === DATA_TYPES.ASSOCIATION &&
                (property as Property & { localField?: string }).localField === fieldName
            ) {
                associationField = property;
            }
        });

        // fallback: try to infer association name from field name
        // example: "productVersionId" -> "product"
        if (!associationField && fieldName.endsWith('VersionId') && fieldName !== 'versionId') {
            const inferredName = fieldName.slice(0, -9);
            const inferredField = schema.getField(inferredName);

            if (inferredField?.type === DATA_TYPES.ASSOCIATION) {
                associationField = inferredField;
            }
        }

        return associationField;
    }

    /**
     * determines if a field is unhandled (not recognized or unsupported).
     */
    isUnhandledField(entityName: string | null | undefined, fieldName: string | null | undefined): boolean {
        if (!entityName) {
            return true;
        }

        if (!Shopware.EntityDefinition.has(entityName)) {
            return true;
        }

        const entityField = this.getEntityField(entityName, fieldName);

        if (!entityField) {
            return true;
        }

        if (UNHANDLED_FIELD_TYPES.includes(entityField.type as (typeof UNHANDLED_FIELD_TYPES)[number])) {
            return true;
        }

        // field type is not supported
        return this.getFieldType(entityName, fieldName) === null;
    }

    /**
     * determines if a field is a scalar field or should be treated as a relation field.
     */
    isScalarField(entityName: string | null | undefined, fieldName: string | null | undefined): boolean {
        const entityField = this.getEntityField(entityName, fieldName);

        if (!entityField) {
            return false;
        }

        if (entityField.type === DATA_TYPES.ASSOCIATION) {
            return false;
        }

        // id fields with corresponding association fields are treated as relation fields
        const correspondingAssociation = this.findCorrespondingAssociationField(entityName, fieldName);

        return !(entityField.type === DATA_TYPES.UUID && correspondingAssociation);
    }

    /**
     * checks if a field is a "to many" association (one_to_many or many_to_many).
     */
    isToManyAssociationField(entityName: string | null | undefined, fieldName: string | null | undefined): boolean {
        const entityField = this.getEntityField(entityName, fieldName);

        if (!entityField || entityField.type !== DATA_TYPES.ASSOCIATION) {
            return false;
        }

        const relationType = entityField.relation;

        return relationType === HANDLED_RELATION_TYPES.ONE_TO_MANY || relationType === HANDLED_RELATION_TYPES.MANY_TO_MANY;
    }

    /**
     * gets the effective entity field to use for a field.
     * for id fields with associations, returns the association field instead.
     */
    getEffectiveEntityField(entityName: string | null | undefined, fieldName: string | null | undefined): Property | null {
        const correspondingAssociation = this.findCorrespondingAssociationField(entityName, fieldName);

        if (correspondingAssociation) {
            return correspondingAssociation;
        }

        return this.getEntityField(entityName, fieldName);
    }

    /**
     * determines the field type for rendering (either component type or relation type).
     */
    getFieldType(entityName: string | null | undefined, fieldName: string | null | undefined): string | null {
        const entityField = this.getEntityField(entityName, fieldName);

        if (!entityField || UNHANDLED_FIELD_TYPES.includes(entityField.type as (typeof UNHANDLED_FIELD_TYPES)[number])) {
            return null;
        }

        // return relation type for association fields
        if (entityField.type === DATA_TYPES.ASSOCIATION && entityField.relation) {
            return entityField.relation as string;
        }

        // return relation type for uuid fields with corresponding association fields
        if (entityField.type === DATA_TYPES.UUID) {
            const correspondingAssociation = this.findCorrespondingAssociationField(entityName, fieldName);

            if (correspondingAssociation?.relation) {
                return correspondingAssociation.relation as string;
            }
        }

        return FIELD_TYPE_COMPONENT_MAPPING[entityField.type as keyof typeof FIELD_TYPE_COMPONENT_MAPPING] ?? null;
    }

    /**
     * sorts fields based on predefined priority. Fields with higher priority appear first.
     * returns a new sorted array without mutating the input.
     */
    sortFieldsByPriority(fields: string[]): string[] {
        return [...fields].sort((a, b) => {
            const priorityA = PRIORITY_FIELD_MAP.get(a);
            const priorityB = PRIORITY_FIELD_MAP.get(b);

            // both fields have defined priorities
            if (priorityA !== undefined && priorityB !== undefined) {
                return priorityA - priorityB;
            }

            // only field A has a defined priority
            if (priorityA !== undefined) {
                return -1;
            }

            // only field B has a defined priority
            if (priorityB !== undefined) {
                return 1;
            }

            return 0;
        });
    }

    /**
     * gets sorted scalar fields from the entity fields, prioritizing required fields first.
     */
    getSortedScalarFields(entityFields: EntityFields, excludeFields: string[] = []): string[] {
        const requiredFieldsSet = new Set(Object.keys(entityFields.required));

        const scalarFields = Object.keys(entityFields.scalar).filter((field) => !excludeFields.includes(field));

        const requiredFields = scalarFields.filter((field) => requiredFieldsSet.has(field));
        const nonRequiredFields = scalarFields.filter((field) => !requiredFieldsSet.has(field));

        const sortedRequiredFields = this.sortFieldsByPriority(requiredFields);
        const sortedNonRequiredFields = this.sortFieldsByPriority(nonRequiredFields);

        return [
            ...sortedRequiredFields,
            ...sortedNonRequiredFields,
        ];
    }

    /**
     * generates table columns for error resolution modal based on entity fields and selected field.
     * the first two columns are fixed (status and selected field), followed by other scalar fields ordered by priority.
     */
    generateTableColumns(entityName: string | null | undefined, selectedFieldName: string): TableColumn[] {
        const fixedColumns: TableColumn[] = [
            {
                label: Shopware.Snippet.tc('swag-migration.index.error-resolution.modals.error.table.columns.status'),
                property: 'status',
                sortable: true,
                position: 1,
                visible: true,
            },
            {
                label: selectedFieldName,
                property: selectedFieldName,
                sortable: true,
                position: 2,
                visible: true,
            },
        ];

        const entityFields = this.extractEntityFields(entityName);
        const allFields = this.getSortedScalarFields(entityFields, [selectedFieldName]);

        const additionalColumns = allFields.map((fieldName, index) => ({
            label: fieldName,
            property: fieldName,
            sortable: true,
            position: 3 + index,
            visible: index < 3,
        }));

        return [
            ...fixedColumns,
            ...additionalColumns,
        ];
    }

    /**
     * gets the highest priority field name from the entity.
     * used to suggest a default field for error resolution, to maximize meaningful data display.
     */
    getHighestPriorityFieldName(entityName: string | null | undefined): string | null {
        const entityFields = this.extractEntityFields(entityName);
        const allFields = this.getSortedScalarFields(entityFields, [
            'id',
            'createdAt',
        ]);

        return allFields[0] || null;
    }

    /**
     * formats association field values to display only ids in a comma-separated list.
     */
    formatAssociationFieldValue(
        entityName: string | null | undefined,
        fieldName: string | null | undefined,
        value: unknown,
    ): string {
        if (!value || typeof value !== 'object') {
            return value ? String(value) : '';
        }

        if (Array.isArray(value)) {
            return value
                .filter(Boolean)
                .map((item) => (typeof item === 'object' && 'id' in item ? String(item.id) : String(item)))
                .join(', ');
        }

        if ('id' in value && value.id) {
            return String(value.id);
        }

        // handle to-many relations where ids are object keys
        if (this.isToManyAssociationField(entityName, fieldName)) {
            return Object.keys(value).join(', ');
        }

        return '';
    }

    /**
     * maps entity field properties from converted data and formats association fields.
     * extracts only the specified properties and formats "to many" association fields to display ids.
     */
    mapEntityFieldProperties(
        entityName: string | null | undefined,
        fieldProperties: string[],
        convertedData: Record<string, unknown>,
        fieldName?: string | null | undefined,
    ): Record<string, unknown> {
        const isToManyRelation = fieldName && this.isToManyAssociationField(entityName, fieldName);
        const dataToMap = isToManyRelation ? this.getFirstNestedItem(convertedData[fieldName]) : convertedData;

        return fieldProperties.reduce<Record<string, unknown>>((acc, property) => {
            if (isToManyRelation && property === fieldName) {
                acc[property] = this.formatAssociationFieldValue(entityName, property, convertedData[property]);

                return acc;
            }

            if (property in dataToMap) {
                const value = dataToMap[property];

                const shouldFormat =
                    this.isToManyAssociationField(entityName, property) ||
                    Array.isArray(value) ||
                    (typeof value === 'object' && value !== null && 'id' in value);

                let finalValue = shouldFormat ? this.formatAssociationFieldValue(entityName, property, value) : value;

                // truncate long text values
                if (typeof finalValue === 'string' && finalValue.length > CONTENT_TEXT_MAX_LENGTH) {
                    finalValue = `${finalValue.substring(0, 100)}...`;
                }

                acc[property] = finalValue;
            }

            return acc;
        }, {});
    }

    private getFirstNestedItem(fieldValue: unknown): Record<string, unknown> {
        if (fieldValue && typeof fieldValue === 'object' && !Array.isArray(fieldValue)) {
            const firstKey = Object.keys(fieldValue)[0];
            const firstItem = firstKey ? (fieldValue as Record<string, unknown>)[firstKey] : null;

            if (firstItem && typeof firstItem === 'object') {
                return firstItem as Record<string, unknown>;
            }
        }

        return {};
    }

    /**
     * validates if a field value is valid for submission based on field type.
     * returns error message snippet suffix if invalid, null if valid.
     */
    validateFieldValue(
        entityName: string | null | undefined,
        fieldName: string | null | undefined,
        fieldValue: unknown,
    ): string | null {
        if (!fieldValue) {
            return 'fieldValueNotSet';
        }

        if (!this.isToManyAssociationField(entityName, fieldName)) {
            return null;
        }

        const isArray = Array.isArray(fieldValue);
        const isEntityCollection = this.isEntityCollection(fieldValue);

        if (!isArray && !isEntityCollection) {
            return 'invalidFieldValueFormat';
        }

        const isEmpty = isArray ? fieldValue.length === 0 : new Array(...(fieldValue as Iterable<unknown>)).length === 0;

        return isEmpty ? 'fieldValueNotSet' : null;
    }

    /**
     * checks if a value is an EntityCollection.
     */
    isEntityCollection(value: unknown): boolean {
        return !!(value && typeof value === 'object' && 'getIds' in value);
    }

    /**
     * normalizes field value for saving, converting EntityCollections to plain arrays.
     */
    normalizeFieldValueForSave(fieldValue: unknown): unknown {
        if (this.isEntityCollection(fieldValue)) {
            // because EntityCollection has a modified map() function
            return new Array(...(fieldValue as Iterable<unknown>));
        }

        return fieldValue;
    }
}
