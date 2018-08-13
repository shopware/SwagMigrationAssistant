import { Component } from 'src/core/shopware';
import template from './sw-progress-bar.html.twig';
import './sw-progress-bar.less';


Component.register('sw-progress-bar', {
    template,

    props: {
        value: {
            type: Number,
            default: 0
        },
        maxValue: {
            type: Number,
            default: 100,
            required: false
        }
    },

    computed: {
        styleWidth() {
            let percentage = this.value / this.maxValue * 100;
            if (percentage > 100) {
                percentage = 100;
            }

            return percentage + '%';
        }
    }
});