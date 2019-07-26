import { Component } from 'src/core/shopware';
import template from './swag-migration-result-screen.html.twig';
import './swag-migration-result-screen.scss';

Component.register('swag-migration-result-screen', {
    template,

    props: {
        runId: {
            type: String,
            required: true
        }
    }
});
