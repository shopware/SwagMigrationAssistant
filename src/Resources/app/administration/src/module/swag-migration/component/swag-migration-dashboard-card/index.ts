import template from './swag-migration-dashboard-card.html.twig';
import './swag-migration-dashboard-card.scss';

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
    },

    methods: {
        goToMigrationIndexPage() {
            this.$router.push({ name: 'swag.migration.index' });
        },
    },
});
