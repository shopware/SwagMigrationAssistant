import template from './swag-migration-profile-shopware-api-page-information.html.twig';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        storeLink() {
            return `https://store.shopware.com/${this.storeLinkISO}/swag226607479310f/migration-connector.html`;
        },

        storeLinkISO() {
            const iso = this.locale.split('-')[0];

            if (
                [
                    'en',
                    'de',
                ].includes(iso)
            ) {
                return iso;
            }

            return 'en';
        },

        locale() {
            return (Shopware.Store.get('session')?.currentLocale as string) ?? '';
        },
    },
});
