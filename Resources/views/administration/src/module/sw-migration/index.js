import { Module } from 'src/core/shopware';
import './page/sw-migration-wizard';
import './component/sw-breadcrumb';

Module.register('sw-migration', {
    type: 'core',
    name: 'Migration',
    description: 'sw-migration.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#ff5555',
    icon: 'default-chart-bar-filled',

    routes: {
        index: {
            component: 'sw-migration-wizard',
            path: 'index'
        }
    },

    navigation: [{
        id: 'sw-migration',
        label: 'sw-migration.general.mainMenuItemGeneral',
        path: 'sw.migration.index',
        color: '#ff5555',
        icon: 'default-chart-bar-filled'
    }]
});
