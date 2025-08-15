import template from './swag-migration-profile-shopware-api-page-information.html.twig';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Shopware.Component.register('swag-migration-profile-shopware-api-page-information', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        storeLink(): string {
            return `https://store.shopware.com/${this.storeLinkISO}/swag226607479310f/migration-connector.html`;
        },

        storeLinkISO(): string {
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

        locale(): string {
            return Shopware.Store.get('session')?.currentLocale ?? '';
        },
    },
});
