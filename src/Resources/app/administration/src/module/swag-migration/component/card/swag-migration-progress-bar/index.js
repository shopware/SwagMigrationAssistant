import template from './swag-migration-progress-bar.html.twig';
import './swag-migration-progress-bar.scss';

const { Component } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-progress-bar', {
    template,

    props: {
        title: {
            type: String,
            default: '',
            required: false,
        },
        leftPointDescription: {
            type: String,
            default: '',
            required: false,
        },
        rightPointDescription: {
            type: String,
            default: '',
            required: false,
        },
        value: {
            type: Number,
            default: 0,
            required: false,
        },
        maxValue: {
            type: Number,
            default: 100,
            required: false,
        },
    },

    computed: {
        rightPointClasses() {
            return {
                'swag-migration-progress-bar__bubble': true,
                'swag-migration-progress-bar__bubble--disabled': this.value < this.maxValue,
                'swag-migration-progress-bar__bubble--active': this.value >= this.maxValue,
            };
        },
    },
});
