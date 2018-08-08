import {Component} from 'src/core/shopware';
import template from './swag-breadcrumb.html.twig';
import './swag-breadcrumb.less';

Component.register('swag-breadcrumb', {
    template,

    props: {
        items: {
            type: Array,
            required: true
        },
        description: {
            type: String,
            required: false
        },
        separatorIcon: {
            type: String,
            required: false,
            default() {
                return 'small-arrow-small-right';
            }
        },
        separatorIconSize: {
            type: String,
            required: false,
            default() {
                return '16';
            }
        }
    }
});
