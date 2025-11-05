import type { Property } from '@administration/src/core/data/entity-definition.data';

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
export const MIGRATION_ERROR_RESOLUTION_SERVICE = 'swagMigrationErrorResolutionService';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default class SwagMigrationErrorResolutionService {
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

    sortFieldsByPriority(fields: string[]): string[] {
        return fields.sort((a, b) => {
            const priorityA = PRIORITY_FIELD_MAP.get(a);
            const priorityB = PRIORITY_FIELD_MAP.get(b);

            if (priorityA !== undefined && priorityB !== undefined) {
                return priorityA - priorityB;
            }

            if (priorityA !== undefined) {
                return -1;
            }

            if (priorityB !== undefined) {
                return 1;
            }

            return 0;
        });
    }

    getSortedScalarFields(entityFields: EntityFields, excludeFields: string[] = []): string[] {
        const requiredFieldsSet = new Set(Object.keys(entityFields.required));

        const scalarFields = Object.keys(entityFields.scalar).filter((field) => !excludeFields.includes(field));

        const requiredFields = scalarFields.filter((field) => requiredFieldsSet.has(field));
        const nonRequiredFields = scalarFields.filter((field) => !requiredFieldsSet.has(field));

        this.sortFieldsByPriority(requiredFields);
        this.sortFieldsByPriority(nonRequiredFields);

        return [
            ...requiredFields,
            ...nonRequiredFields,
        ];
    }

    generateTableColumns(
        entityName: string | null | undefined,
        selectedFieldName: string,
        selectedFieldLabel?: string,
    ): TableColumn[] {
        const columns: TableColumn[] = [
            {
                label: Shopware.Snippet.tc('swag-migration.index.error-resolution.modals.error.table.columns.status'),
                property: 'status',
                sortable: true,
                position: 1,
                visible: true,
            },
            {
                label: selectedFieldLabel || selectedFieldName,
                property: selectedFieldName,
                sortable: true,
                position: 2,
                visible: true,
            },
        ];

        const entityFields = this.extractEntityFields(entityName);
        const allFields = this.getSortedScalarFields(entityFields, [selectedFieldName]);

        allFields.forEach((fieldName, index) => {
            columns.push({
                label: fieldName,
                property: fieldName,
                sortable: true,
                position: 3 + index,
                visible: index < 3,
            });
        });

        return columns;
    }

    getHighestPriorityFieldName(entityName: string | null | undefined): string | null {
        const entityFields = this.extractEntityFields(entityName);
        const allFields = this.getSortedScalarFields(entityFields, [
            'id',
            'createdAt',
        ]);

        return allFields[0] || null;
    }
}
