import template from './swag-migration-wizard-page-credentials-error.html.twig';
import './swag-migration-wizard-page-credentials-error.scss';

const { Component } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-wizard-page-credentials-error', {
    template,

    props: {
        errorMessageSnippet: {
            type: String,
            default: '',
            required: false,
        },

        errorMessageHintSnippet: {
            type: String,
            default: null,
            required: false,
        },

        errorMessageHintType: {
            type: String,
            default: 'info',
            required: false,
            validValues: ['info', 'warning', 'error', 'success', 'neutral'],
            validator(value) {
                return ['info', 'warning', 'error', 'success', 'neutral'].includes(value);
            },
        },
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },
});
