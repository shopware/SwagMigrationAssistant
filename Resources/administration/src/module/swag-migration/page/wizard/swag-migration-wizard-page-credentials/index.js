import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-credentials.html.twig';

Component.register('swag-migration-wizard-page-credentials', {
    template,

    props: {
        credentialsComponent: {
            type: String,
            default: ''
        },

        credentials: {
            type: Object,
            default() {
                return {};
            }
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        componentIsLoaded() {
            return Component.getComponentRegistry().has(this.credentialsComponent);
        }
    }
});
