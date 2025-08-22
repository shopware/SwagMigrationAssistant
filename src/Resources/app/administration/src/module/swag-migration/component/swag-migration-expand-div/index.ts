import template from './swag-migration-expand-div.html.twig';
import './swag-migration-expand-div.scss';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        expandTitle: {
            type: String,
            default: '',
            required: false,
        },
        collapseTitle: {
            type: String,
            default: '',
            required: false,
        },
    },

    data() {
        return {
            isExpanded: false,
        };
    },

    methods: {
        onClick() {
            this.isExpanded = !this.isExpanded;
        },
    },
});
