import { Module } from 'src/core/shopware';
import './page/swag-migration-index';
import './page/swag-migration-wizard';
import './page/swag-migration-wizard-page-1';
import './page/swag-migration-wizard-page-2';
import './page/swag-migration-wizard-page-3';
import './page/swag-migration-wizard-page-4';
import './page/swag-migration-wizard-page-4-success';
import './page/swag-migration-wizard-page-4-error';
import './component/swag-breadcrumb';
import './component/swag-dot-navigation';
import './component/swag-svg-step1';
import './component/swag-svg-step2';
import './component/swag-svg-step3';
import './component/swag-svg-step4-success';
import './component/swag-svg-step4-error';
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
            path: 'wizard',
            redirect: { name: 'swag.migration.wizard.wizard1' },
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
                },
                wizard4_success: {
                    path: 'wizard4/success',
                    component: 'swag-migration-wizard-page-4-success'
                },
                wizard4_error: {
                    path: 'wizard4/error',
                    component: 'swag-migration-wizard-page-4-error'
                }
            }
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
