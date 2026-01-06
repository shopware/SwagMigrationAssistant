/**
 * @private
 */
export const fixturePreMapping = Object.freeze([
    {
        entity: 'salutation',
        choices: [
            { uuid: 'mr', description: 'mr', extensions: [] },
            { uuid: 'mrs', description: 'mrs', extensions: [] },
            { uuid: 'not_specified', description: 'not_specified', extensions: [] },
        ],
        mapping: [
            {
                id: 'salutation-mr',
                sourceId: 'mr',
                description: 'mr',
                destinationUuid: 'mr',
                extensions: [],
            },
            {
                id: 'salutation-ms',
                sourceId: 'ms',
                description: 'ms',
                destinationUuid: 'mrs',
                extensions: [],
            },
        ],
    },
]);

/**
 * @private
 */
export const fixtureDataSelection = Object.freeze([
    {
        id: 'customersOrders',
        total: 2,
        dataType: 'basicData',
        position: 200,
        processMediaFiles: true,
        requiredSelection: false,
        snippet: 'swag-migration.index.selectDataCard.dataSelection.customersOrders',
        dataSets: [
            {},
            {},
            {},
            {},
        ],
        dataSetsRequiredForCount: [
            {},
            {},
        ],
        entityNamesRequiredForCount: [
            'customer',
            'order',
        ],
        entityTotals: {
            customer: 1,
            shipping_method: 1,
            order: 1,
            order_document: 1,
        },
        entityNames: {
            customer_custom_field: 'swag-migration.index.selectDataCard.entities.customer_custom_field',
            customer: 'swag-migration.index.selectDataCard.entities.customer',
            shipping_method: 'swag-migration.index.selectDataCard.entities.shipping_method',
            order_custom_field: 'swag-migration.index.selectDataCard.entities.order_custom_field',
            order: 'swag-migration.index.selectDataCard.entities.order',
            order_document_custom_field: 'swag-migration.index.selectDataCard.entities.order_document_custom_field',
            order_document: 'swag-migration.index.selectDataCard.entities.order_document',
        },
    },
]);

/**
 * @private
 */
export const fixtureEnvironmentInformation = Object.freeze({
    extensions: [],
    displayWarnings: [],
    migrationDisabled: false,
    sourceSystemName: 'Shopware',
    sourceSystemVersion: '5.5',
    sourceSystemDomain: 'sw55.local',
    targetSystemCurrency: 'EUR',
    sourceSystemCurrency: 'EUR',
    sourceSystemLocale: 'de-DE',
    targetSystemLocale: 'de-DE',
    additionalData: [],
    totals: {
        product: {
            entityName: 'product',
            total: 2,
        },
    },
});

/**
 * @private
 */
export const fixtureLogGroups = Object.freeze([
    {
        // scalar
        code: 'SWAG_MIGRATION_VALIDATION_MISSING_REQUIRED_FIELD',
        count: 591,
        entityName: 'media',
        fieldName: 'createdAt',
        fixCount: 591,
        gatewayName: 'local',
        profileName: 'shopware55',
        resolved: true,
    },
    {
        // relation
        code: 'SWAG_MIGRATION_VALIDATION_INVALID_REQUIRED_FIELD_VALUE',
        count: 161,
        entityName: 'product',
        fieldName: 'options',
        fixCount: 13,
        gatewayName: 'local',
        profileName: 'shopware55',
        resolved: false,
    },
]);

/**
 * @private
 */
export const fixtureLogs = Object.freeze([
    {
        // scalar
        id: '019ab13b59507022854ac8ae183190e3',
        entityId: '019ab13b594f73ebbdd60d37c3aeb90e',
        sourceData: null,
        convertedData: {
            id: '019ab13b594f73ebbdd60d37c3aeb90e',
            mediaFolderId: '019ab13b248b7008869973ca6e264a1d',
            title: 'Muensterlaender_Lagerkorn_Ballons_Hochformat',
            translations: {
                '019aa6ac1d56709bb3cb1ce1a276ed12': {
                    id: '019ab13b594f73ebbdd60d37c55a2246',
                    languageId: '019aa6ac1d56709bb3cb1ce1a276ed12',
                    title: 'Muensterlaender_Lagerkorn_Ballons_Hochformat',
                },
            },
        },
    },
    {
        // relation
        id: '019ab13b36b970f896355882cc635dcd',
        entityId: '019ab13b36b27204b34ab120455e7180',
        sourceData: {
            id: '2',
            mode: '0',
            name: 'Münsterländer Lagerkorn 32%',
            datum: '2012-08-15',
            shops: ['1'],
            taxID: '1',
            active: '1',
            categories: [
                { id: '14', path: '|5|3|' },
                { id: '21', path: '|10|3|' },
                { id: '50', path: '|43|39|' },
                { id: '67', path: '|61|39|' },
            ],
        },
        convertedData: {
            options: [
                {
                    id: '019ab13b36b37022bb2fac5ef21abce6',
                    name: '1,5 Liter',
                    group: {
                        id: '019ab13b29c7711486573559ad13c70c',
                        name: 'Flascheninhalt',
                        translations: {
                            '019aa6ac1d56709bb3cb1ce1a276ed12': {
                                id: '019ab13b29c7711486573559aede5582',
                                name: 'Flascheninhalt',
                                languageId: '019aa6ac1d56709bb3cb1ce1a276ed12',
                            },
                        },
                    },
                    position: 5,
                    translations: {
                        '019aa6ac1d56709bb3cb1ce1a276ed12': {
                            id: '019ab13b36b37022bb2fac5ef3132829',
                            name: '1,5 Liter',
                            position: 5,
                            languageId: '019aa6ac1d56709bb3cb1ce1a276ed12',
                        },
                    },
                },
            ],
        },
    },
]);

/**
 * @private
 */
export const fixtureFixes = Object.freeze([
    {
        id: '019ab6275a2471c0b938e0584b9a550f',
        value: '2025-11-24T13:57:00.000Z',
        entityId: '019ab13b594f73ebbdd60d37c3aeb90e',
    },
]);
