import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-credentials-error.html.twig';

Component.register('swag-migration-wizard-page-credentials-error', {
    template,

    props: {
        errorMessage: {
            type: String,
            default: '',
            required: false
        }
    }
});
