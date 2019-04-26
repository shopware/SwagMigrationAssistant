import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-profile-information.html.twig';

Component.register('swag-migration-wizard-page-profile-information', {
    template,

    props: {
        profileInformationComponent: {
            type: String,
            default: ''
        }
    },

    computed: {
        componentIsLoaded() {
            return Component.getComponentRegistry().has(this.profileInformationComponent);
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    }
});
