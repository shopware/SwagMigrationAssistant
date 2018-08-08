import { Module } from 'src/core/shopware';
import './page/swag-migration-wizard';
import './page/swag-migration-wizard-page-1';
import './page/swag-migration-wizard-page-2';
import './page/swag-migration-wizard-page-3';
import './page/swag-migration-wizard-page-4';
import './component/swag-breadcrumb';
import './component/swag-dot-navigation';
import './component/swag-svg-step1';
import './component/swag-svg-step2';
import './component/swag-svg-step3';


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
            component: 'swag-migration-wizard',
            path: 'index',
            redirect: { name: 'sw.migration.index.wizard1' },
            children: {
                wizard1: {
                    path: 'wizard1',
                    component: 'swag-migration-wizard-page-1'
                },
                wizard2: {
                    path: 'wizard2',
                    component: 'swag-migration-wizard-page-2'
                },
                wizard3: {
                    path: 'wizard3',
                    component: 'swag-migration-wizard-page-3'
                },
                wizard4: {
                    path: 'wizard4',
                    component: 'swag-migration-wizard-page-4'
                }
            }
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
