import template from './swag-migration-wizard-page-credentials.html.twig';

const { Component } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-wizard-page-credentials', {
    template,

    props: {
        credentialsComponent: {
            type: String,
            default: '',
        },

        credentials: {
            type: Object,
            default() {
                return {};
            },
        },
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        componentIsLoaded() {
            return Component.getComponentRegistry().has(this.credentialsComponent);
        },
    },
});
