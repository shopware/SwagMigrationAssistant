import template from './swag-migration-wizard-page-credentials.html.twig';

const { Component } = Shopware;

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Component.register('swag-migration-wizard-page-credentials', {
    template,

    emits: ['onCredentialsChanged', 'onChildRouteReadyChanged', 'onTriggerPrimaryClick'],

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
