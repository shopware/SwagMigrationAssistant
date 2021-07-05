import template from './swag-migration-result-screen.html.twig';
import './swag-migration-result-screen.scss';

const { Component } = Shopware;

Component.register('swag-migration-result-screen', {
    template,

    props: {
        runId: {
            type: String,
            required: true,
        },
    },
});
