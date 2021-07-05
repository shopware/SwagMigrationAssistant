import template from './swag-migration-wizard-page-credentials-error.html.twig';

const { Component } = Shopware;

Component.register('swag-migration-wizard-page-credentials-error', {
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
