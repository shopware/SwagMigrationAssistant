export const fixturePreMapping = [
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
];

export const fixtureDataSelection = [
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
];

export const fixtureEnvironmentInformation = {
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
};
