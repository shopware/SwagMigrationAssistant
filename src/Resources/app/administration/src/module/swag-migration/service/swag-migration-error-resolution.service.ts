import type { Property } from '@administration/src/core/data/entity-definition.data';
import type EntityDefinition from '@administration/src/core/data/entity-definition.data';

/**
 * @private
 * structure holding categorized entity fields.
 */
export interface EntityFields {
    scalar: Record<string, Property>;
    associations: Record<string, Property>;
    required: Record<string, Property>;
}

/**
 * @private
 * table column definition for error resolution modal.
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
 * data types used in entity definitions.
 * `scalarTypes` & `jsonTypes` in `entity-definition.data.ts`
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
 * list of field types that are not handled for error resolution.
 * blob and password fields are excluded due to their sensitive or complex nature.
 */
export const UNHANDLED_FIELD_TYPES = [
    'blob',
    'password',
] as const;

/**
 * @private
 * list of field names that are not handled for error resolution.
 */
export const UNHANDLED_FIELD_NAMES = [
    'id',
    'autoIncrement',
] as const;

/**
 * @private
 * list of relation types that are handled for error resolution.
 * `Property.relation` in `entity-definition.data.ts`
 */
export const HANDLED_RELATION_TYPES = {
    MANY_TO_ONE: 'many_to_one',
    ONE_TO_MANY: 'one_to_many',
    MANY_TO_MANY: 'many_to_many',
} as const;

/**
 * @private
 * field component types used for rendering fields in the ui.
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
 * mapping of data types to corresponding field component types for rendering in the ui.
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
 * list of fields prioritized for sorting purposes, to determined most meaningful fields first.
 */
export const PRIORITY_FIELDS = [
    'name',
    'technicalName',
    'number',
    'productNumber',
    'orderNumber',
    'customerNumber',
    'code',
    'active',
    'visible',
    'status',
    'type',
    'available',
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
    'id',
    'createdAt',
] as const;

/**
 * @private
 * mapping of priority fields to their respective priority index for quick lookup.
 */
export const PRIORITY_FIELD_MAP: Map<string, number> = new Map(
    PRIORITY_FIELDS.map((field, index) => [
        field,
        index,
    ]),
);

/**
 * @private
 */
export const CONTENT_TEXT_MAX_LENGTH = 100;

/**
 * @private
 * result of resolving a nested field path.
 */
export interface ResolvedFieldPath {
    schema: EntityDefinition<never>;
    property: Property;
    fieldName: string;
}

/**
 * @private
 */
export const MIGRATION_ERROR_TRANSLATION_SNIPPET_PREFIX = 'swag-migration.index.error-resolution.codes';

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
     * translates a migration error code into a human-readable message.
     * if no translation is found, returns the original code.
     */
    translateErrorCode(code: string): string {
        const translationKey = `${MIGRATION_ERROR_TRANSLATION_SNIPPET_PREFIX}.${code}`;

        if (!Shopware.Snippet.te(translationKey)) {
            return code;
        }

        return Shopware.Snippet.t(translationKey);
    }

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

        const translationSuffix = '_translation';

        if (entityName.endsWith(translationSuffix)) {
            const baseEntityName = entityName.slice(0, -translationSuffix.length);
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
        if (!entityName || !Shopware.EntityDefinition.has(entityName)) {
            return { scalar: {}, associations: {}, required: {} };
        }

        const definition = Shopware.EntityDefinition.get(entityName);

        return {
            scalar: definition.filterProperties((property) => definition.isScalarField(property)),
            associations: definition.getToOneAssociations(),
            required: definition.getRequiredFields(),
        };
    }

    /**
     * gets the entity schema for a given entity name.
     */
    getEntitySchema(entityName: string | null | undefined): EntityDefinition<never> | null {
        return entityName && Shopware.EntityDefinition.has(entityName) ? Shopware.EntityDefinition.get(entityName) : null;
    }

    /**
     * resolves a potentially nested field path (e.g., "prices.shippingMethodId") to its target.
     * traverses through association fields to find the final schema and property.
     */
    resolveFieldPath(entityName: string | null | undefined, fieldPath: string | null | undefined): ResolvedFieldPath | null {
        if (!fieldPath || !entityName) {
            return null;
        }

        const initialSchema = this.getEntitySchema(entityName);

        if (!initialSchema) {
            return null;
        }

        const paths = fieldPath.split('.');

        return (
            paths.reduce<{ schema: EntityDefinition<never>; result: ResolvedFieldPath | null } | null>(
                (acc, path, index) => {
                    if (!acc || acc.result) {
                        return acc;
                    }

                    const property = acc.schema.getField(path);

                    if (!property) {
                        return null;
                    }

                    if (index === paths.length - 1) {
                        return { ...acc, result: { schema: acc.schema, property, fieldName: path } };
                    }

                    if (property.type !== DATA_TYPES.ASSOCIATION || !property.entity) {
                        return null;
                    }

                    const nextSchema = this.getEntitySchema(property.entity);

                    return nextSchema ? { schema: nextSchema, result: null } : null;
                },
                { schema: initialSchema, result: null },
            )?.result ?? null
        );
    }

    /**
     * gets the entity field definition for a specific field.
     */
    getEntityField(entityName: string | null | undefined, fieldName: string | null | undefined): Property | null {
        const lastFieldName = fieldName?.split('.').pop();

        if (!lastFieldName || (UNHANDLED_FIELD_NAMES as readonly string[]).includes(lastFieldName)) {
            return null;
        }

        return this.resolveFieldPath(entityName, fieldName)?.property ?? null;
    }

    /**
     * finds the corresponding association field for an id field.
     * for example: "productVersionId", "productId" => "product" association.
     */
    findCorrespondingAssociationField(
        entityName: string | null | undefined,
        fieldName: string | null | undefined,
    ): Property | null {
        const resolved = this.resolveFieldPath(entityName, fieldName);

        if (!resolved) {
            return null;
        }

        const { schema, property, fieldName: actualFieldName } = resolved;

        if (property.type !== DATA_TYPES.UUID || property.flags?.primary_key) {
            return null;
        }

        const byLocalField = Object.values(schema.properties).find(
            (prop) =>
                prop.type === DATA_TYPES.ASSOCIATION &&
                (prop as Property & { localField?: string }).localField === actualFieldName,
        );

        if (byLocalField) {
            return byLocalField;
        }

        const versionIdSuffix = 'VersionId';

        if (actualFieldName.endsWith(versionIdSuffix) && actualFieldName !== 'versionId') {
            const inferredField = schema.getField(actualFieldName.slice(0, -versionIdSuffix.length));

            if (inferredField?.type === DATA_TYPES.ASSOCIATION) {
                return inferredField;
            }
        }

        return null;
    }

    /**
     * determines if a field is unhandled (not recognized or unsupported).
     */
    isUnhandledField(entityName: string | null | undefined, fieldName: string | null | undefined): boolean {
        if (!entityName || !Shopware.EntityDefinition.has(entityName)) {
            return true;
        }

        const entityField = this.getEntityField(entityName, fieldName);

        if (!entityField || (UNHANDLED_FIELD_TYPES as readonly string[]).includes(entityField.type)) {
            return true;
        }

        return this.getFieldType(entityName, fieldName) === null;
    }

    /**
     * determines if a field is a scalar field or should be treated as a relation field.
     */
    isScalarField(entityName: string | null | undefined, fieldName: string | null | undefined): boolean {
        const entityField = this.getEntityField(entityName, fieldName);

        if (!entityField || entityField.type === DATA_TYPES.ASSOCIATION) {
            return false;
        }

        const correspondingAssociation = this.findCorrespondingAssociationField(entityName, fieldName);

        return !(entityField.type === DATA_TYPES.UUID && correspondingAssociation);
    }

    /**
     * checks if a field is a "to many" association (one_to_many or many_to_many).
     * supports nested field paths like "prices.shippingMethodId".
     */
    isToManyAssociationField(entityName: string | null | undefined, fieldName: string | null | undefined): boolean {
        const resolved = this.resolveFieldPath(entityName, fieldName);

        return resolved ? resolved.schema.isToManyAssociation(resolved.property) : false;
    }

    /**
     * gets the effective entity field to use for a field.
     * for id fields with associations, returns the association field instead.
     */
    getEffectiveEntityField(entityName: string | null | undefined, fieldName: string | null | undefined): Property | null {
        return this.findCorrespondingAssociationField(entityName, fieldName) ?? this.getEntityField(entityName, fieldName);
    }

    /**
     * determines the field type for rendering (either component type or relation type).
     */
    getFieldType(entityName: string | null | undefined, fieldName: string | null | undefined): string | null {
        const entityField = this.getEntityField(entityName, fieldName);

        if (!entityField || (UNHANDLED_FIELD_TYPES as readonly string[]).includes(entityField.type)) {
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

            // fallback to alphabetical order
            return a.localeCompare(b);
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
     * gets the highest priority field name from the entity (excluding id and createdAt).
     * used to suggest a default field for error resolution, to maximize meaningful data display.
     */
    getHighestPriorityFieldName(entityName: string | null | undefined): string | null {
        const entityFields = this.extractEntityFields(entityName);
        const excludeFields = [
            'id',
            'createdAt',
            'global',
        ];

        const scalarFields = Object.keys(entityFields.scalar).filter((field) => !excludeFields.includes(field));

        if (scalarFields.length === 0) {
            return null;
        }

        const sortedByPriority = this.sortFieldsByPriority(scalarFields);

        return sortedByPriority[0] || null;
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
     * supports nested field paths like "prices.shippingMethodId".
     */
    mapEntityFieldProperties(
        entityName: string | null | undefined,
        fieldProperties: string[],
        convertedData: Record<string, unknown>,
    ): Record<string, unknown> {
        return fieldProperties.reduce<Record<string, unknown>>((acc, property) => {
            const value = Shopware.Utils.object.get(convertedData, property) as unknown;

            if (value === undefined) {
                return acc;
            }

            const shouldFormat =
                this.isToManyAssociationField(entityName, property) ||
                Array.isArray(value) ||
                (typeof value === 'object' && value !== null && 'id' in value);

            let finalValue = shouldFormat ? this.formatAssociationFieldValue(entityName, property, value) : value;

            // truncate long text values
            if (typeof finalValue === 'string' && finalValue.length > CONTENT_TEXT_MAX_LENGTH) {
                finalValue = `${finalValue.substring(0, CONTENT_TEXT_MAX_LENGTH)}...`;
            }

            Shopware.Utils.object.set(acc, property, finalValue);

            return acc;
        }, {});
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
        if (fieldValue === null || fieldValue === undefined || fieldValue === '') {
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
