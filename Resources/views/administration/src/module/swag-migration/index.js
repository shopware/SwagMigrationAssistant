import { Module } from 'src/core/shopware';
import './page/swag-migration-index';
import './page/swag-migration-main-page';
import './page/swag-migration-history';
import './page/swag-migration-wizard';
import './page/swag-migration-wizard-page-introduction';
import './page/swag-migration-wizard-page-plugin-information';
import './page/swag-migration-wizard-page-credentials';
import './page/swag-migration-wizard-page-credentials-success';
import './page/swag-migration-wizard-page-credentials-error';
import './component/swag-breadcrumb';
import './component/swag-dot-navigation';
import './component/swag-migration-data-selector';
import './component/swag-migration-loading-screen';
import './component/swag-migration-loading-screen-success';
import './component/swag-migration-loading-screen-warning';
import './component/swag-migration-loading-screen-failure';
import './component/swag-migration-loading-screen-pause';
import './component/swag-migration-loading-screen-takeover';
import './component/swag-migration-shop-information';
import './component/sw-progress-bar';
import './component/swag-migration-history-selected-data';
import './extension/sw-settings-index';

Module.register('swag-migration', {
    type: 'plugin',
    name: 'swag-migration.general.mainMenuItemGeneral',
    description: 'swag-migration.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'default-action-settings',

    routes: {
        index: {
            component: 'swag-migration-index',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            },
            redirect: { name: 'swag.migration.index.main' },
            children: {
                main: {
                    path: 'main',
                    component: 'swag-migration-main-page'
                },
                history: {
                    path: 'history',
                    component: 'swag-migration-history'
                }
            }
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
        parent: 'sw-settings',
        label: 'swag-migration.general.mainMenuItemGeneral',
        path: 'swag.migration.index',
        color: '#9AA8B5',
        icon: 'default-chart-bar-filled'
    }]
});
