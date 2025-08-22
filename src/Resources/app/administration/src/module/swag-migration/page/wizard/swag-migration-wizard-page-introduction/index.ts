import template from './swag-migration-wizard-page-introduction.html.twig';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },
});
