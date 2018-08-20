import {Module} from 'src/core/shopware';
import './page/swag-migration-index';
import './page/swag-migration-wizard';
import './page/swag-migration-wizard-page-introduction';
import './page/swag-migration-wizard-page-plugin-information';
import './page/swag-migration-wizard-page-credentials';
import './page/swag-migration-wizard-page-credentials-success';
import './page/swag-migration-wizard-page-credentials-error';
import './component/swag-breadcrumb';
import './component/swag-dot-navigation';
import './component/swag-svg-wizard-introduction';
import './component/swag-svg-wizard-plugin-information';
import './component/swag-svg-wizard-credentials-success';
import './component/swag-svg-wizard-credentials-error';
import './component/swag-svg-wizard-credentials-failure';
import './component/swag-migration-data-selector';
import './component/swag-migration-loading-screen';
import './component/swag-migration-loading-screen-success';
import './component/swag-migration-loading-screen-warning';
import './component/swag-migration-loading-screen-failure';
import './component/sw-progress-bar';


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
            redirect: { name: 'swag.migration.wizard.introduction' },
            children: {
                introduction: {
                    path: 'introduction',
                    component: 'swag-migration-wizard-page-introduction'
                },
                plugin_information: {
                    path: 'plugin-information',
                    component: 'swag-migration-wizard-page-plugin-information'
                },
                credentials: {
                    path: 'credentials',
                    component: 'swag-migration-wizard-page-credentials'
                },
                credentials_success: {
                    path: 'credentials/success',
                    component: 'swag-migration-wizard-page-credentials-success'
                },
                credentials_error: {
                    path: 'credentials/error',
                    component: 'swag-migration-wizard-page-credentials-error'
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
