import template from './swag-migration-settings-icon.html.twig';
import './swag-migration-settings-icon.scss';

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
});
