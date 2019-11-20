import template from './sw-dashboard-index.html.twig';
import './sw-dashboard-index.scss';

const { Component } = Shopware;

Component.override('sw-dashboard-index', {
    template
});
