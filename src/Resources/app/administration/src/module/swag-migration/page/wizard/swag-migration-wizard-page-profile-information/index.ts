import template from './swag-migration-wizard-page-profile-information.html.twig';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        profileInformationComponent: {
            type: String,
            default: '',
        },
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        componentIsLoaded() {
            return Shopware.Component.getComponentRegistry().has(this.profileInformationComponent);
        },
    },
});
