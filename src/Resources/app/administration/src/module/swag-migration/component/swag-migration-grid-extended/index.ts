import template from './swag-migration-grid-extended.html.twig';

type GridItem = {
    id: string;
    isDeleted?: boolean;
    isLocal?: boolean;
    [key: string]: unknown;
};

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        disabledAttribute: {
            type: String,
            default: 'disabled',
        },
    },

    methods: {
        isDisabled(item: GridItem) {
            return !!item[this.disabledAttribute];
        },

        extendedGridRowClasses(item: GridItem, index: number) {
            const classes = {
                'is--selected': this.isSelected(item.id) && !this.isDisabled(item),
                'is--deleted': item.isDeleted,
                'is--new': item.isLocal,
                'is--disabled': this.isDisabled(item),
            };

            classes[`sw-grid__row--${index}`] = true;

            return classes;
        },
    },
});
