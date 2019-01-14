import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-credentials.html.twig';

Component.register('swag-migration-wizard-page-credentials', {
    template,

    props: {
        profileName: {
            type: String
        },

        gatewayName: {
            type: String
        },

        credentials: {
            type: Object,
            default() {
                return {};
            }
        }
    }
});
