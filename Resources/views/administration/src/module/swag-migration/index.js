import { Module } from 'src/core/shopware';
import './page/swag-migration-wizard';
import './page/swag-migration-index';
import './component/sw-breadcrumb';
import './component/swag-migration-data-selector';

Module.register('swag-migration', {
    type: 'plugin',
    name: 'swag-migration.general.mainMenuItemGeneral',
    description: 'swag-migration.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#ff5555',
    icon: 'default-chart-bar-filled',

    routes: {
        index: {
            component: 'swag-migration-index',
            path: 'index'
        },
        wizard: {
            component: 'swag-migration-wizard',
            path: 'wizard'
        }
    },

    navigation: [{
        id: 'swag-migration',
        label: 'swag-migration.general.mainMenuItemGeneral',
        path: 'swag.migration.index',
        color: '#ff5555',
        icon: 'default-chart-bar-filled'
    }]
});
