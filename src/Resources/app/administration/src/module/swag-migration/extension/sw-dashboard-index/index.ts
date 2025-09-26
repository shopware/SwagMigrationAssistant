import template from './sw-dashboard-index.html.twig';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
    ],

    computed: {
        isMigrationAllowed(): boolean {
            return this.acl.isAdmin();
        },
    },
});
