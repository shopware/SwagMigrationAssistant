import template from './swag-migration-assistant.html.twig';
import './swag-migration-assistant.scss';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Shopware.Component.register('swag-migration-assistant', {
    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    template,
});
