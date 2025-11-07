import template from './swag-migration-tab-card-item.html.twig';

/**
 * @private
 */
export interface SwagMigrationTabCardItemData {
    active: boolean;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    data(): SwagMigrationTabCardItemData {
        return {
            active: false,
        };
    },

    methods: {
        setActive(active: boolean) {
            this.active = active;
        },
    },
});
