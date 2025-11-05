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
            const indexA = PRIORITY_FIELDS.indexOf(a as (typeof PRIORITY_FIELDS)[number]);
            const indexB = PRIORITY_FIELDS.indexOf(b as (typeof PRIORITY_FIELDS)[number]);

            if (indexA !== -1 && indexB !== -1) {
                return indexA - indexB;
            }

            if (indexA !== -1) {
                return -1;
            }

            if (indexB !== -1) {
                return 1;
            }

            return 0;
        });
    }

    generateTableColumns(entityFields: EntityFields, selectedFieldName: string, selectedFieldLabel?: string): TableColumn[] {
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

        const scalarFields = Object.keys(entityFields.scalar);
        const requiredFields = Object.keys(entityFields.required);

        const availableScalarFields = scalarFields.filter((field) => field !== selectedFieldName);
        const availableRequiredFields = requiredFields.filter((field) => availableScalarFields.includes(field));

        const nonRequiredFields = availableScalarFields.filter((field) => !availableRequiredFields.includes(field));

        const sortedRequiredFields = this.sortFieldsByPriority([...availableRequiredFields]);
        const sortedNonRequiredFields = this.sortFieldsByPriority([...nonRequiredFields]);

        const allFields = [
            ...sortedRequiredFields,
            ...sortedNonRequiredFields,
        ];

        const visibleFields = allFields.slice(0, 3);

        visibleFields.forEach((fieldName, index) => {
            columns.push({
                label: fieldName,
                property: fieldName,
                sortable: true,
                position: 3 + index,
                visible: true,
            });
        });

        const hiddenFields = allFields.slice(3);

        hiddenFields.forEach((fieldName, index) => {
            columns.push({
                label: fieldName,
                property: fieldName,
                sortable: true,
                position: 3 + visibleFields.length + index,
                visible: false,
            });
        });

        return columns;
    }
}
