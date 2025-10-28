import template from './swag-migration-error-resolution-details-modal.html.twig';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        modalTitle() {
            return this.$tc('swag-migration.index.error-resolution.modals.details.title', {
                entityName: 'product',
                value: 'name',
            });
        },
    },
});
