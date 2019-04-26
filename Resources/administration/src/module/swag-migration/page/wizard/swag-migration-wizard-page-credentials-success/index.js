import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-credentials-success.html.twig';

Component.register('swag-migration-wizard-page-credentials-success', {
    template,

    props: {
        errorMessageSnippet: {
            type: String,
            default: '',
            required: false
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    }
});
