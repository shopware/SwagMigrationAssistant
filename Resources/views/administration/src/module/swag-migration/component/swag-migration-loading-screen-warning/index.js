import { Component } from 'src/core/shopware';
import template from './swag-migration-loading-screen-warning.html.twig';
import './swag-migration-loading-screen-warning.less';

Component.register('swag-migration-loading-screen-warning', {
    template,

    props: {
        errorList: {
            type: Array
        }
    }
});
