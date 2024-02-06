import template from './swag-migration-expand-div.html.twig';
import './swag-migration-expand-div.scss';

const { Component } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-expand-div', {
    template,

    props: {
        expandTitle: {
            type: String,
            default: '',
            required: false,
        },

        collapseTitle: {
            type: String,
            default: '',
            required: false,
        },
    },

    data() {
        return {
            isExpanded: false,
        };
    },

    methods: {
        onClick() {
            this.isExpanded = !this.isExpanded;
        },
    },
});
