import template from './swag-migration-wizard-page-profile-information.html.twig';

const { Component } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-wizard-page-profile-information', {
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
            return Component.getComponentRegistry().has(this.profileInformationComponent);
        },
    },
});
