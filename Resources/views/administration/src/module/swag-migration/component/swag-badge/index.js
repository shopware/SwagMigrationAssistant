import { Component } from 'src/core/shopware';
import template from './swag-badge.html.twig';
import './swag-badge.less';

Component.register('swag-badge', {
    template,

    props: {
        label: {
            type: String,
            default: '',
            required: true
        },

        variant: {
            type: String,
            default: 'default'
        }
    },

    computed: {
        variantClass() {
            return `is--${this.variant}`;
        }
    }
});
