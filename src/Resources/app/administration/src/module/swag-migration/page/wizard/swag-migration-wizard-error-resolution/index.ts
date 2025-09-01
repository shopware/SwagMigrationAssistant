import template from './swag-migration-wizard-error-resolution.html.twig';

const MOCK_LOGS = [
    {
        entityName: 'product',
        fieldName: 'name', // text
    },
    {
        entityName: 'product',
        fieldName: 'price', // json
    },
    {
        entityName: 'product',
        fieldName: 'description', // textarea
    },
    {
        entityName: 'product',
        fieldName: 'available', // boolean
    },
    {
        entityName: 'product',
        fieldName: 'availableStock', // number
    },
    {
        entityName: 'product',
        fieldName: 'releaseDate', // date
    },
    {
        entityName: 'product',
        fieldName: 'children', // one-to-many
    },
    {
        entityName: 'product',
        fieldName: 'cmsPage', // many-to-one
    },
];

/**
 * @private
 */
export interface SwagMigrationWizardErrorResolutionData {}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        logs() {
            return MOCK_LOGS;
        },
    },
});
