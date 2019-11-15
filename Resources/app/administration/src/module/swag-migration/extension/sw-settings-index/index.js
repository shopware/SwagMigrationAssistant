import template from './sw-settings-index.html.twig';
import './sw-settings-index.scss';

const { Component } = Shopware;

Component.override('sw-settings-index', {
    template
});
