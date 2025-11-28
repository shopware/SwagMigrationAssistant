/**
 * @package after-sales
 */
import SwagMigrationErrorResolutionService, {
    DATA_TYPES,
    UNHANDLED_FIELD_TYPES,
    HANDLED_RELATION_TYPES,
    FIELD_COMPONENT_TYPES,
    FIELD_TYPE_COMPONENT_MAPPING,
    PRIORITY_FIELDS,
    PRIORITY_FIELD_MAP,
    CONTENT_TEXT_MAX_LENGTH,
} from 'SwagMigrationAssistant/module/swag-migration/service/swag-migration-error-resolution.service';

const ENTITY_LINK_TESTS = [
    {
        name: 'no entity name',
        entityName: '',
        routes: {},
        expected: null,
    },
    {
        name: 'valid index route',
        entityName: 'product',
        routes: {
            index: {
                routeKey: 'index',
                name: 'test.product.index',
            },
        },
        expected: {
            name: 'test.product.index',
        },
    },
    {
        name: 'invalid index route',
        entityName: 'product',
        routes: {
            index: {
                routeKey: 'index',
            },
        },
        expected: null,
    },
    {
        name: 'found translation route',
        entityName: 'product_translation',
        routes: {
            index: {
                routeKey: 'index',
                name: 'test.product.index',
            },
        },
        expected: {
            name: 'test.product.index',
        },
    },
    {
        name: 'not found translation module',
        entityName: 'product_translation',
        routes: null,
        expected: null,
    },
    {
        name: 'not found translation route',
        entityName: 'product_translation',
        routes: {},
        expected: null,
    },
    {
        name: 'no link',
        entityName: 'unknown_entity',
        routes: null,
        expected: null,
    },
];

const ENTITY_SCHEMA_TESTS = [
    {
        name: 'null entity name',
        entityName: null,
        expected: null,
    },
    {
        name: 'unknown entity name',
        entityName: 'unknown_entity',
        expected: null,
    },
    {
        name: 'whitespace entity name',
        entityName: '   ',
        expected: null,
    },
    {
        name: 'known entity name',
        entityName: 'product',
        expected: expect.objectContaining({
            entity: 'product',
        }),
    },
];

const ENTITY_FIELD_TESTS = [
    {
        name: 'null field name',
        entityName: 'product',
        fieldName: null,
        expected: null,
    },
    {
        name: 'forbidden field name',
        entityName: 'product',
        fieldName: UNHANDLED_FIELD_TYPES.at(0),
        expected: null,
    },
    {
        name: 'null entity name',
        entityName: null,
        fieldName: 'id',
        expected: null,
    },
    {
        name: 'unknown entity name',
        entityName: 'unknown_entity',
        fieldName: 'id',
        expected: null,
    },
    {
        name: 'unknown field name',
        entityName: 'product',
        fieldName: 'unknown_field',
        expected: null,
    },
    {
        name: 'known entity & field name',
        entityName: 'product',
        fieldName: 'taxId',
        expected: expect.objectContaining({
            type: 'uuid',
        }),
    },
];

const EXTRACT_ENTITY_FIELD_TESTS = [
    {
        name: 'undefined entity name',
        entityName: null,
        expected: {
            scalar: {},
            associations: {},
            required: {},
        },
    },
    {
        name: 'undefined entity name',
        entityName: 'unknown_entity',
        expected: {
            scalar: {},
            associations: {},
            required: {},
        },
    },
    {
        name: 'valid entity',
        entityName: 'media',
        expected: {
            scalar: expect.objectContaining({
                id: expect.objectContaining({ type: 'uuid' }),
                alt: expect.objectContaining({ type: 'text' }),
                uploadedAt: expect.objectContaining({ type: 'date' }),
                fileSize: expect.objectContaining({ type: 'int' }),
                url: expect.objectContaining({ type: 'string' }),
                hasFile: expect.objectContaining({ type: 'boolean' }),
            }),
            associations: expect.objectContaining({
                user: expect.objectContaining({ relation: 'many_to_one' }),
            }),
            required: expect.objectContaining({
                id: expect.objectContaining({ type: 'uuid' }),
                createdAt: expect.objectContaining({ type: 'date' }),
            }),
        },
    },
];

const UNHANDLED_FIELD_TESTS = [
    {
        name: 'undefined entity name',
        entityName: null,
        fieldName: 'id',
        expected: true,
    },
    {
        name: 'unknown entity name',
        entityName: 'unknown_entity',
        fieldName: 'id',
        expected: true,
    },
    {
        name: 'unknown field name',
        entityName: 'media',
        fieldName: 'unknown_field',
        expected: true,
    },
    {
        name: 'in list of unhandled field types',
        entityName: 'customer',
        fieldName: 'password',
        expected: true,
    },
    {
        name: 'handable field type',
        entityName: 'customer',
        fieldName: 'firstName',
        expected: false,
    },
    {
        name: 'unhandable field type',
        entityName: 'mediaFolder',
        fieldName: 'path',
        expected: true,
    },
];

const SCALAR_FIELD_TESTS = [
    {
        name: 'unknown entity name',
        entityName: 'unknown_entity',
        fieldName: 'id',
        expected: false,
    },
    {
        name: 'unknown field name',
        entityName: 'product',
        fieldName: 'unknown_field',
        expected: false,
    },
    {
        name: 'field is association',
        entityName: 'product',
        fieldName: 'manufacturer',
        expected: false,
    },
    {
        name: 'uuid field',
        entityName: 'product',
        fieldName: 'taxId',
        expected: false,
    },
    {
        name: 'int field',
        entityName: 'product',
        fieldName: 'maxPurchase',
        expected: true,
    },
    {
        name: 'text field',
        entityName: 'media',
        fieldName: 'fileName',
        expected: true,
    },
    {
        name: 'float field',
        entityName: 'product',
        fieldName: 'purchaseUnit',
        expected: true,
    },
    {
        name: 'string field',
        entityName: 'product',
        fieldName: 'displayGroup',
        expected: true,
    },
    {
        name: 'boolean field',
        entityName: 'media',
        fieldName: 'hasFile',
        expected: true,
    },
    {
        name: 'date field',
        entityName: 'media',
        fieldName: 'uploadedAt',
        expected: true,
    },
];

const TO_MANY_ASSOCIATION_FIELD_TESTS = [
    {
        name: 'unknown entity name',
        entityName: 'unknown_entity',
        fieldName: 'tags',
        expected: false,
    },
    {
        name: 'unknown field name',
        entityName: 'product',
        fieldName: 'unknown_field',
        expected: false,
    },
    {
        name: 'not an association field',
        entityName: 'media',
        fieldName: 'url',
        expected: false,
    },
    {
        name: 'one-to-many association field',
        entityName: 'media',
        fieldName: 'productManufacturers',
        expected: true,
    },
    {
        name: 'many-to-many association field',
        entityName: 'media',
        fieldName: 'tags',
        expected: true,
    },
];

const FIELD_TYPE_TESTS = [
    {
        name: 'undefined entity name',
        entityName: null,
        fieldName: 'id',
        expected: null,
    },
    {
        name: 'undefined field name',
        entityName: 'product',
        fieldName: null,
        expected: null,
    },
    {
        name: 'unhandled field type',
        entityName: 'customer',
        fieldName: 'password',
        expected: null,
    },
    {
        name: 'association field (many-to-one)',
        entityName: 'product',
        fieldName: 'manufacturer',
        expected: 'many_to_one',
    },
    {
        name: 'association field (many-to-many)',
        entityName: 'product',
        fieldName: 'categories',
        expected: 'many_to_many',
    },
    {
        name: 'uuid with corresponding association',
        entityName: 'product',
        fieldName: 'parentVersionId',
        expected: 'many_to_one',
    },
    {
        name: 'uuid without corresponding association',
        entityName: 'category',
        fieldName: 'afterCategoryVersionId',
        expected: null,
    },
    {
        name: 'int field',
        entityName: 'product',
        fieldName: 'maxPurchase',
        expected: 'number',
    },
    {
        name: 'text field',
        entityName: 'media',
        fieldName: 'fileName',
        expected: 'textarea',
    },
    {
        name: 'float field',
        entityName: 'product',
        fieldName: 'purchaseUnit',
        expected: 'number',
    },
    {
        name: 'string field',
        entityName: 'product',
        fieldName: 'displayGroup',
        expected: 'text',
    },
    {
        name: 'boolean field',
        entityName: 'media',
        fieldName: 'hasFile',
        expected: 'switch',
    },
    {
        name: 'date field',
        entityName: 'media',
        fieldName: 'uploadedAt',
        expected: 'datepicker',
    },
    {
        name: 'json list field',
        entityName: 'product',
        fieldName: 'customFields',
        expected: 'editor',
    },
    {
        name: 'json object field',
        entityName: 'document',
        fieldName: 'config',
        expected: 'editor',
    },
];

const CORRESPONDING_ASSOCIATION_FIELD_TESTS = [
    {
        name: 'null entity name',
        entityName: null,
        fieldName: 'parentId',
        expected: null,
    },
    {
        name: 'undefined field name',
        entityName: 'product',
        fieldName: undefined,
        expected: null,
    },
    {
        name: 'not an uuid field',
        entityName: 'product',
        fieldName: 'maxPurchase',
        expected: null,
    },
    {
        name: 'primary key field',
        entityName: 'product_category',
        fieldName: 'productId',
        expected: null,
    },
    {
        name: 'field with corresponding association',
        entityName: 'product',
        fieldName: 'taxId',
        expected: expect.objectContaining({
            entity: 'tax',
            localField: 'taxId',
            referenceField: 'id',
            relation: 'many_to_one',
            type: 'association',
        }),
    },
    {
        name: 'field without corresponding association or translation match',
        entityName: 'category',
        fieldName: 'afterCategoryVersionId',
        expected: undefined,
    },
    {
        name: 'infer translation association from field name',
        entityName: 'product',
        fieldName: 'parentVersionId',
        expected: expect.objectContaining({
            entity: 'product',
            localField: 'parentId',
            referenceField: 'id',
            relation: 'many_to_one',
            type: 'association',
        }),
    },
];

const EFFECTIVE_ENTITY_FIELD_TESTS = [
    {
        name: 'null entity name',
        entityName: null,
        fieldName: 'parentId',
        expected: null,
    },
    {
        name: 'undefined entity name',
        entityName: undefined,
        fieldName: 'parentId',
        expected: null,
    },
    {
        name: 'null field name',
        entityName: 'product',
        fieldName: null,
        expected: null,
    },
    {
        name: 'undefined field name',
        entityName: 'product',
        fieldName: undefined,
        expected: null,
    },
    {
        name: 'unknown entity name',
        entityName: 'unknown_entity',
        fieldName: 'name',
        expected: null,
    },
    {
        name: 'unknown field name',
        entityName: 'product',
        fieldName: 'unknown_field',
        expected: null,
    },
    {
        name: 'direct association field',
        entityName: 'product',
        fieldName: 'tax',
        expected: expect.objectContaining({
            entity: 'tax',
            localField: 'taxId',
            referenceField: 'id',
            relation: 'many_to_one',
            type: 'association',
        }),
    },
    {
        name: 'inferred association from UUID field',
        entityName: 'product',
        fieldName: 'taxId',
        expected: expect.objectContaining({
            entity: 'tax',
            localField: 'taxId',
            referenceField: 'id',
            relation: 'many_to_one',
            type: 'association',
        }),
    },
];

const SORT_FIELDS_BY_PRIORITY_TESTS = [
    {
        name: 'empty field list',
        fields: [],
        expected: [],
    },
    {
        name: 'fields in random order',
        fields: [
            'name',
            'id',
            'createdAt',
            'taxId',
            'unknown_field',
            'updatedAt',
            'description',
        ],
        expected: [
            'name',
            'description',
            'id',
            'createdAt',
            'taxId',
            'unknown_field',
            'updatedAt',
        ],
    },
    {
        name: 'priority list',
        fields: [...PRIORITY_FIELDS].reverse(),
        expected: [...PRIORITY_FIELDS],
    },
];

const SORTED_SCALAR_FIELD_TESTS = [
    {
        name: 'unknown entity name',
        entityName: 'unknown_entity',
        excluded: [],
        expected: [],
    },
    {
        name: 'media',
        entityName: 'media',
        excluded: [],
        expected: [
            'id',
            'createdAt',
            'title',
            'alt',
            'url',
            'path',
            'fileExtension',
            'fileHash',
            'fileName',
            'fileSize',
            'hasFile',
            'mediaFolderId',
            'mediaTypeRaw',
            'mimeType',
            'private',
            'thumbnailsRo',
            'updatedAt',
            'uploadedAt',
            'userId',
        ],
    },
    {
        name: 'media with exclusions',
        entityName: 'media',
        excluded: [
            'fileSize',
            'url',
            'createdAt',
        ],
        expected: [
            'id',
            'title',
            'alt',
            'path',
            'fileExtension',
            'fileHash',
            'fileName',
            'hasFile',
            'mediaFolderId',
            'mediaTypeRaw',
            'mimeType',
            'private',
            'thumbnailsRo',
            'updatedAt',
            'uploadedAt',
            'userId',
        ],
    },
];

const HIGHEST_PRIORITY_FIELD_TESTS = [
    {
        name: 'unknown entity name',
        entityName: 'unknown_entity',
        expected: null,
    },
    {
        name: 'media entity',
        entityName: 'media',
        expected: 'title',
    },
    {
        name: 'product entity',
        entityName: 'product',
        expected: 'name',
    },
    {
        name: 'category entity',
        entityName: 'category',
        expected: 'name',
    },
    {
        name: 'customer entity',
        entityName: 'customer',
        expected: 'customerNumber',
    },
    {
        name: 'seo_url entity',
        entityName: 'seo_url',
        expected: 'foreignKey',
    },
];

const GENERATE_TABLE_COLUMNS_TESTS = [
    {
        name: 'undefined entity name',
        entityName: null,
        selectedFieldName: 'name',
        expected: [
            'status',
            'name',
        ],
    },
    {
        name: 'media entity',
        entityName: 'media',
        selectedFieldName: 'path',
        expected: [
            'status',
            'path',
            'id',
            'createdAt',
            'title',
        ],
    },
    {
        name: 'product entity',
        entityName: 'product',
        selectedFieldName: 'name',
        expected: [
            'status',
            'name',
            'productNumber',
            'id',
            'createdAt',
        ],
    },
    {
        name: 'customer entity',
        entityName: 'customer',
        selectedFieldName: 'updatedAt',
        expected: [
            'status',
            'updatedAt',
            'customerNumber',
            'email',
            'firstName',
        ],
    },
    {
        name: 'seo_url entity',
        entityName: 'seo_url',
        selectedFieldName: 'languageId',
        expected: [
            'status',
            'languageId',
            'id',
            'createdAt',
            'foreignKey',
        ],
    },
];

const FORMAT_ASSOCIATION_FIELD_VALUE_TESTS = [
    {
        name: 'null value',
        entityName: null,
        fieldName: null,
        value: null,
        expected: '',
    },
    {
        name: 'non object value - boolean',
        entityName: null,
        fieldName: null,
        value: true,
        expected: 'true',
    },
    {
        name: 'non object value - number',
        entityName: null,
        fieldName: null,
        value: 42,
        expected: '42',
    },
    {
        name: 'non object value - string',
        entityName: null,
        fieldName: null,
        value: 'test-string',
        expected: 'test-string',
    },
    {
        name: 'empty array',
        entityName: 'product',
        fieldName: 'categories',
        value: [],
        expected: '',
    },
    {
        name: 'array of entities with id field',
        entityName: 'product',
        fieldName: 'categories',
        value: [
            { id: 'id-1' },
            { id: 'id-2' },
            { id: 'id-3' },
        ],
        expected: 'id-1, id-2, id-3',
    },
    {
        name: 'single entity with id field',
        entityName: 'product',
        fieldName: 'manufacturer',
        value: { id: 'manu-1' },
        expected: 'manu-1',
    },
    {
        name: 'single entity without id field',
        entityName: 'product',
        fieldName: 'manufacturer',
        value: { name: 'Manufacturer Name' },
        expected: '',
    },
    {
        name: 'to many association',
        entityName: 'product',
        fieldName: 'tags',
        value: [
            { id: 'tag-1' },
            { id: 'tag-2' },
        ],
        expected: 'tag-1, tag-2',
    },
    {
        name: 'unknown association',
        entityName: 'product',
        fieldName: 'unknown_association',
        expected: '',
    },
    {
        name: 'unknown entity name',
        entityName: 'unknown_entity',
        fieldName: 'field',
        value: { id: 'test-1' },
        expected: 'test-1',
    },
];

const MAP_ENTITY_FIELD_PROPERTIES_TESTS = [
    {
        name: 'empty field properties',
        entityName: 'product',
        fieldProperties: [],
        convertedData: { id: 'prod-1', name: 'Product 1' },
        fieldName: null,
        expected: {},
    },
    {
        name: 'simple scalar fields',
        entityName: 'product',
        fieldProperties: [
            'id',
            'name',
            'productNumber',
        ],
        convertedData: { id: 'prod-1', name: 'Product 1', productNumber: 'P-001' },
        fieldName: null,
        expected: { id: 'prod-1', name: 'Product 1', productNumber: 'P-001' },
    },
    {
        name: 'missing properties in data',
        entityName: 'product',
        fieldProperties: [
            'id',
            'name',
            'description',
        ],
        convertedData: { id: 'prod-1', name: 'Product 1' },
        fieldName: null,
        expected: { id: 'prod-1', name: 'Product 1' },
    },
    {
        name: 'association field with id property',
        entityName: 'product',
        fieldProperties: [
            'id',
            'manufacturer',
        ],
        convertedData: {
            id: 'prod-1',
            manufacturer: { id: 'manu-1', name: 'Manufacturer 1' },
        },
        fieldName: null,
        expected: {
            id: 'prod-1',
            manufacturer: 'manu-1',
        },
    },
    {
        name: 'to-many association field',
        entityName: 'product',
        fieldProperties: [
            'id',
            'categories',
        ],
        convertedData: {
            id: 'prod-1',
            categories: [
                { id: 'cat-1' },
                { id: 'cat-2' },
            ],
        },
        fieldName: null,
        expected: {
            id: 'prod-1',
            categories: 'cat-1, cat-2',
        },
    },
    {
        name: 'nested to-many relation data',
        entityName: 'product',
        fieldProperties: [
            'id',
            'categories',
        ],
        convertedData: {
            id: 'prod-1',
            categories: {
                'cat-1': { id: 'cat-1', name: 'Category 1' },
                'cat-2': { id: 'cat-2', name: 'Category 2' },
            },
        },
        fieldName: 'categories',
        expected: {
            id: 'cat-1',
            categories: 'cat-1, cat-2',
        },
    },
    {
        name: 'nested to-many relation with null value',
        entityName: 'product',
        fieldProperties: [
            'id',
            'categories',
        ],
        convertedData: {
            id: 'prod-1',
            categories: null,
        },
        fieldName: 'categories',
        expected: {
            categories: '',
        },
    },
    {
        name: 'too long text field',
        entityName: 'order',
        fieldProperties: [
            'customerComment',
        ],
        convertedData: {
            customerComment: 'A'.repeat(CONTENT_TEXT_MAX_LENGTH + 1),
        },
        fieldName: 'customerComment',
        expected: {
            customerComment: `${'A'.repeat(CONTENT_TEXT_MAX_LENGTH)}...`,
        },
    },
];

const VALIDATE_FIELD_VALUE_TESTS = [
    {
        name: 'null value',
        entityName: 'product',
        fieldName: 'name',
        fieldValue: null,
        expected: 'fieldValueNotSet',
    },
    {
        name: 'undefined value',
        entityName: 'product',
        fieldName: 'name',
        fieldValue: undefined,
        expected: 'fieldValueNotSet',
    },
    {
        name: 'empty string value',
        entityName: 'product',
        fieldName: 'name',
        fieldValue: '',
        expected: 'fieldValueNotSet',
    },
    {
        name: 'whitespace-only string value',
        entityName: 'product',
        fieldName: 'name',
        fieldValue: '   ',
        expected: null,
    },
    {
        name: 'valid scalar field value',
        entityName: 'product',
        fieldName: 'name',
        fieldValue: 'Product Name',
        expected: null,
    },
    {
        name: 'zero as valid number value',
        entityName: 'product',
        fieldName: 'maxPurchase',
        fieldValue: 0,
        expected: null,
    },
    {
        name: 'false as valid boolean value',
        entityName: 'media',
        fieldName: 'hasFile',
        fieldValue: false,
        expected: null,
    },
    {
        name: 'empty array for to-many association',
        entityName: 'product',
        fieldName: 'categories',
        fieldValue: [],
        expected: 'fieldValueNotSet',
    },
    {
        name: 'valid array for to-many association',
        entityName: 'product',
        fieldName: 'categories',
        fieldValue: [{ id: 'cat-1' }],
        expected: null,
    },
    {
        name: 'invalid object for to-many association',
        entityName: 'product',
        fieldName: 'categories',
        fieldValue: { id: 'cat-1' },
        expected: 'invalidFieldValueFormat',
    },
    {
        name: 'empty entity collection for to-many association',
        entityName: 'product',
        fieldName: 'categories',
        fieldValue: {
            getIds: () => [],
            *[Symbol.iterator]() {
                yield* [];
            },
        },
        expected: 'fieldValueNotSet',
    },
    {
        name: 'valid entity collection for to-many association',
        entityName: 'product',
        fieldName: 'categories',
        fieldValue: {
            getIds: () => ['cat-1'],
            *[Symbol.iterator]() {
                yield { id: 'cat-1' };
            },
        },
        expected: null,
    },
];

const IS_ENTITY_COLLECTION_TESTS = [
    {
        name: 'null value',
        value: null,
        expected: false,
    },
    {
        name: 'number',
        value: 42,
        expected: false,
    },
    {
        name: 'plain array',
        value: [{ id: 'id-1' }],
        expected: false,
    },
    {
        name: 'plain object',
        value: { id: 'id-1' },
        expected: false,
    },
    {
        name: 'object with getIds method',
        value: {
            getIds: () => ['id-1'],
        },
        expected: true,
    },
    {
        name: 'EntityCollection-like object',
        value: {
            getIds: () => [
                'id-1',
                'id-2',
            ],
            *[Symbol.iterator]() {
                yield { id: 'id-1' };
                yield { id: 'id-2' };
            },
        },
        expected: true,
    },
];

const NORMALIZE_FIELD_VALUE_FOR_SAVE_TESTS = [
    {
        name: 'null value',
        fieldValue: null,
        expected: null,
    },
    {
        name: 'undefined value',
        fieldValue: undefined,
        expected: undefined,
    },
    {
        name: 'plain array',
        fieldValue: [
            { id: 'id-1' },
            { id: 'id-2' },
        ],
        expected: [
            { id: 'id-1' },
            { id: 'id-2' },
        ],
    },
    {
        name: 'empty array',
        fieldValue: [],
        expected: [],
    },
    {
        name: 'plain object',
        fieldValue: { id: 'id-1', name: 'Test' },
        expected: { id: 'id-1', name: 'Test' },
    },
    {
        name: 'empty object',
        fieldValue: {},
        expected: {},
    },
    {
        name: 'string value',
        fieldValue: 'test-string',
        expected: 'test-string',
    },
    {
        name: 'number value',
        fieldValue: 42,
        expected: 42,
    },
    {
        name: 'boolean value',
        fieldValue: false,
        expected: false,
    },
    {
        name: 'EntityCollection to array',
        fieldValue: {
            getIds: () => [
                'id-1',
                'id-2',
            ],
            *[Symbol.iterator]() {
                yield { id: 'id-1' };
                yield { id: 'id-2' };
            },
        },
        expected: [
            { id: 'id-1' },
            { id: 'id-2' },
        ],
    },
    {
        name: 'empty EntityCollection to empty array',
        fieldValue: {
            getIds: () => [],
            *[Symbol.iterator]() {
                yield* [];
            },
        },
        expected: [],
    },
];

const testCases = {
    getEntityLink: ENTITY_LINK_TESTS,
    getEntitySchema: ENTITY_SCHEMA_TESTS,
    getEntityField: ENTITY_FIELD_TESTS,
    extractEntityFields: EXTRACT_ENTITY_FIELD_TESTS,
    isUnhandledField: UNHANDLED_FIELD_TESTS,
    isScalarField: SCALAR_FIELD_TESTS,
    isToManyAssociationField: TO_MANY_ASSOCIATION_FIELD_TESTS,
    getFieldType: FIELD_TYPE_TESTS,
    findCorrespondingAssociationField: CORRESPONDING_ASSOCIATION_FIELD_TESTS,
    getEffectiveEntityField: EFFECTIVE_ENTITY_FIELD_TESTS,
    sortFieldsByPriority: SORT_FIELDS_BY_PRIORITY_TESTS,
    getSortedScalarFields: SORTED_SCALAR_FIELD_TESTS,
    getHighestPriorityFieldName: HIGHEST_PRIORITY_FIELD_TESTS,
    generateTableColumns: GENERATE_TABLE_COLUMNS_TESTS,
    formatAssociationFieldValue: FORMAT_ASSOCIATION_FIELD_VALUE_TESTS,
    mapEntityFieldProperties: MAP_ENTITY_FIELD_PROPERTIES_TESTS,
    validateFieldValue: VALIDATE_FIELD_VALUE_TESTS,
    isEntityCollection: IS_ENTITY_COLLECTION_TESTS,
    normalizeFieldValueForSave: NORMALIZE_FIELD_VALUE_FOR_SAVE_TESTS,
};

describe('module/swag-migration/service/swag-migration-error-resolution.service', () => {
    const service = new SwagMigrationErrorResolutionService();

    describe('constants', () => {
        it('should handle all relevant scalar & json field types', () => {
            // must match `scalarTypes` & `jsonTypes` in `entity-definition.data.ts`
            expect(Object.values(DATA_TYPES)).toStrictEqual([
                'uuid',
                'int',
                'text',
                'float',
                'string',
                'boolean',
                'date',
                'json_list',
                'json_object',
                'association',
            ]);

            expect(UNHANDLED_FIELD_TYPES).toStrictEqual([
                'blob',
                'password',
            ]);
        });

        it('should handle relevant relation types', () => {
            // must match `Property.relation` in `entity-definition.data.ts`
            expect(Object.values(HANDLED_RELATION_TYPES)).toStrictEqual([
                'many_to_one',
                'one_to_many',
                'many_to_many',
            ]);
        });

        it('should provide field type components & correct mapping', () => {
            // all ui component types used for field rendering
            expect(Object.values(FIELD_COMPONENT_TYPES)).toStrictEqual([
                'number',
                'textarea',
                'text',
                'switch',
                'datepicker',
                'editor',
            ]);

            const mappedTypes = Object.keys(FIELD_TYPE_COMPONENT_MAPPING);
            const expectedMappedTypes = [
                'int',
                'text',
                'float',
                'string',
                'boolean',
                'date',
                'json_list',
                'json_object',
            ];
            expect(mappedTypes.sort()).toStrictEqual(expectedMappedTypes.sort());

            expect(FIELD_TYPE_COMPONENT_MAPPING).toStrictEqual({
                int: 'number',
                text: 'textarea',
                float: 'number',
                string: 'text',
                boolean: 'switch',
                date: 'datepicker',
                json_list: 'editor',
                json_object: 'editor',
            });

            Object.values(FIELD_TYPE_COMPONENT_MAPPING).forEach((component) => {
                expect(Object.values(FIELD_COMPONENT_TYPES)).toContain(component);
            });
        });

        it('should provide priority field list & map in sync', () => {
            expect(PRIORITY_FIELDS).toHaveLength(49);
            expect(PRIORITY_FIELD_MAP.size).toBe(PRIORITY_FIELDS.length);

            expect(PRIORITY_FIELD_MAP).toStrictEqual(
                new Map(
                    PRIORITY_FIELDS.map((field, index) => [
                        field,
                        index,
                    ]),
                ),
            );

            const uniqueFields = new Set(PRIORITY_FIELDS);
            expect(uniqueFields.size).toBe(PRIORITY_FIELDS.length);
        });

        it('should set a max text content lenght', () => {
            expect(CONTENT_TEXT_MAX_LENGTH).toBe(100);
        });
    });

    describe('routing', () => {
        it.each(testCases.getEntityLink)('should generate entity link: $name', ({ expected, entityName, routes }) => {
            const isTranslation = entityName.includes('translation');
            const hasRoutes = routes !== null;

            const firstMockValue = isTranslation
                ? undefined
                : {
                      routes: new Map(Object.entries(routes ?? [])),
                  };

            const secondMockValue = !hasRoutes
                ? undefined
                : {
                      routes: new Map(Object.entries(routes)),
                  };

            const moduleSpy = jest
                .spyOn(Shopware.Module, 'getModuleByEntityName')
                .mockReturnValueOnce(firstMockValue)
                .mockReturnValueOnce(secondMockValue);

            const result = service.getEntityLink(entityName);

            expect(result).toStrictEqual(expected);
            moduleSpy.mockRestore();
        });
    });

    describe('entity schema access', () => {
        it.each(testCases.getEntitySchema)('should find entity schema: $name', ({ entityName, expected }) => {
            expect(service.getEntitySchema(entityName)).toStrictEqual(expected);
        });

        it.each(testCases.getEntityField)(
            'should find specific entity field definition: $name',
            ({ entityName, fieldName, expected }) => {
                expect(service.getEntityField(entityName, fieldName)).toStrictEqual(expected);
            },
        );

        it.each(testCases.extractEntityFields)(
            'should extract entity fields from schema: $name',
            ({ entityName, expected }) => {
                expect(service.extractEntityFields(entityName)).toStrictEqual(expected);
            },
        );
    });

    describe('entity field classification', () => {
        it.each(testCases.isUnhandledField)(
            'should recognize unhandled field types: $name',
            ({ entityName, fieldName, expected }) => {
                expect(service.isUnhandledField(entityName, fieldName)).toStrictEqual(expected);
            },
        );

        it.each(testCases.isScalarField)('should recognize scalar fields: $name', ({ entityName, fieldName, expected }) => {
            expect(service.isScalarField(entityName, fieldName)).toStrictEqual(expected);
        });

        it.each(testCases.isToManyAssociationField)(
            'should recognize to-many association fields: $name',
            ({ entityName, fieldName, expected }) => {
                expect(service.isToManyAssociationField(entityName, fieldName)).toStrictEqual(expected);
            },
        );

        it.each(testCases.getFieldType)('should get field type: $name', ({ entityName, fieldName, expected }) => {
            expect(service.getFieldType(entityName, fieldName)).toStrictEqual(expected);
        });
    });

    describe('association field resolution', () => {
        it.each(testCases.findCorrespondingAssociationField)(
            'should find corresponding association: $name',
            ({ entityName, fieldName, expected }) => {
                expect(service.findCorrespondingAssociationField(entityName, fieldName)).toStrictEqual(expected);
            },
        );

        it.each(testCases.getEffectiveEntityField)(
            'should find effective valid entity field: $name',
            ({ entityName, fieldName, expected }) => {
                expect(service.getEffectiveEntityField(entityName, fieldName)).toStrictEqual(expected);
            },
        );
    });

    describe('field sorting', () => {
        it.each(testCases.sortFieldsByPriority)('should sort fields by priority', ({ fields, expected }) => {
            expect(service.sortFieldsByPriority(fields)).toStrictEqual(expected);
        });

        it.each(testCases.getSortedScalarFields)(
            'should sort scalar fields: $name',
            ({ entityName, excluded, expected }) => {
                const fields = service.extractEntityFields(entityName);
                expect(fields.scalar).toBeDefined();

                expect(service.getSortedScalarFields(fields, excluded)).toStrictEqual(expected);
            },
        );

        it.each(testCases.getHighestPriorityFieldName)(
            'should get highest priority field name: $name',
            ({ entityName, expected }) => {
                expect(service.getHighestPriorityFieldName(entityName)).toStrictEqual(expected);
            },
        );
    });

    describe('table column generation', () => {
        it.each(testCases.generateTableColumns)(
            'should generate table columns from entity fields: $name',
            ({ entityName, selectedFieldName, expected }) => {
                const result = service.generateTableColumns(entityName, selectedFieldName);

                // only 5 columns should be visible max
                const visibleColumns = result.filter((column) => column.visible);
                expect(visibleColumns.length).toBeLessThanOrEqual(5);

                const visibleFieldNames = visibleColumns.map((column) => column.property);
                expect(visibleFieldNames).toStrictEqual(expected);

                // positions should be unique
                const positionSet = new Set(result.map((column) => column.position));
                expect(positionSet.size).toBe(result.length);

                result.forEach((column, index) => {
                    expect(column).toHaveProperty('label');
                    expect(column).toHaveProperty('property');
                    expect(column).toHaveProperty('visible');

                    // positions should be sequential
                    expect(column.position).toBe(index + 1);
                    expect(column.sortable).toBe(true);
                });
            },
        );
    });

    describe('value transformation', () => {
        it.each(testCases.formatAssociationFieldValue)(
            'should format association field values for display: $name',
            ({ entityName, fieldName, value, expected }) => {
                expect(service.formatAssociationFieldValue(entityName, fieldName, value)).toStrictEqual(expected);
            },
        );

        it.each(testCases.mapEntityFieldProperties)(
            'should map entity field properties: $name',
            ({ entityName, fieldProperties, convertedData, fieldName, expected }) => {
                expect(
                    service.mapEntityFieldProperties(entityName, fieldProperties, convertedData, fieldName),
                ).toStrictEqual(expected);
            },
        );
    });

    describe('value validation', () => {
        it.each(testCases.validateFieldValue)(
            'should validate field values: $name',
            ({ entityName, fieldName, fieldValue, expected }) => {
                expect(service.validateFieldValue(entityName, fieldName, fieldValue)).toStrictEqual(expected);
            },
        );

        it.each(testCases.isEntityCollection)('should check if value is entity collection: $name', ({ value, expected }) => {
            expect(service.isEntityCollection(value)).toStrictEqual(expected);
        });

        it.each(testCases.normalizeFieldValueForSave)(
            'should normalize field value for save: $name',
            ({ fieldValue, expected }) => {
                expect(service.normalizeFieldValueForSave(fieldValue)).toStrictEqual(expected);
            },
        );
    });
});
