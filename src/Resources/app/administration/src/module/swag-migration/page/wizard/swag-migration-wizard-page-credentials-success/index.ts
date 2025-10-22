import template from './swag-migration-wizard-page-credentials-success.html.twig';
import './swag-migration-wizard-page-credentials-success.scss';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        errorMessageSnippet: {
            type: String,
            default: '',
            required: false,
        },
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },
});
