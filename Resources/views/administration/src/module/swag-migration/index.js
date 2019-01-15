import { Module } from 'src/core/shopware';

import './mixin';
import './component';
import './page';
import './extension/sw-settings-index';
import './profile';

Module.register('swag-migration', {
    type: 'plugin',
    name: 'swag-migration.general.mainMenuItemGeneral',
    description: 'swag-migration.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'default-action-settings',

    routes: {
        emptyScreen: {
            component: 'swag-migration-empty-screen',
            path: 'empty',
            meta: {
                parentPath: 'sw.settings.index'
            }
        },
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
                    component: 'swag-migration-history',
                    children: {
                        detail: {
                            path: 'detail/:id',
                            component: 'swag-migration-history-detail'
                        }
                    }
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
                profile: {
                    path: 'profile',
                    component: 'swag-migration-wizard-page-profile'
                },
                profileCreate: {
                    path: 'profile/create',
                    component: 'swag-migration-wizard-page-profile-create'
                },
                profileSelect: {
                    path: 'profile/select',
                    component: 'swag-migration-wizard-page-profile-select'
                },
                profileInformation: {
                    path: 'profile/information',
                    component: 'swag-migration-wizard-page-profile-information'
                },
                credentials: {
                    path: 'credentials',
                    component: 'swag-migration-wizard-page-credentials'
                },
                credentialsSuccess: {
                    path: 'credentials/success',
                    component: 'swag-migration-wizard-page-credentials-success'
                },
                credentialsError: {
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
