import { Component } from 'src/core/shopware';
import template from './swag-dot-navigation.html.twig';
import './swag-dot-navigation.less';

Component.register('swag-dot-navigation', {
    template,

    props: {
        navCount: {
            type: Number,
            default: 1
        },

        navIndex: {
            type: Number,
            default: 0
        }
    }
});