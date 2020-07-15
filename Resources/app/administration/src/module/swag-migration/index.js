import './mixin';
import './component';
import './page';
import './extension/sw-settings-index';
import './extension/sw-dashboard-index';
import './profile';
import MigrationProcessStore from '../../core/data/migrationProcess.store';
import MigrationUIStore from '../../core/data/migrationUI.store';

const { Module, State } = Shopware;

State.registerModule('swagMigration/process', MigrationProcessStore);
State.registerModule('swagMigration/ui', MigrationUIStore);

Module.register('swag-migration', {
    type: 'plugin',
    name: 'swag-migration',
    title: 'swag-migration.general.mainMenuItemGeneral',
    description: 'swag-migration.general.descriptionTextModule',
    version: '0.9.0',
    targetVersion: '0.9.0',
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
                    component: 'swag-migration-main-page',
                    meta: {
                        parentPath: 'sw.settings.index',
                        resetMigration: false
                    }
                },
                resetMigration: {
                    path: 'reset-migration',
                    component: 'swag-migration-main-page',
                    meta: {
                        parentPath: 'sw.settings.index',
                        resetMigration: true
                    }
                },
                history: {
                    path: 'history',
                    component: 'swag-migration-history',
                    children: {
                        detail: {
                            path: 'detail/:id',
                            component: 'swag-migration-history-detail',
                            meta: {
                                parentPath: 'sw.settings.index'
                            }
                        }
                    },
                    meta: {
                        parentPath: 'sw.settings.index'
                    }
                },
                dataSelector: {
                    path: 'dataSelector',
                    component: 'swag-migration-data-selector',
                    meta: {
                        parentPath: 'sw.settings.index'
                    }
                }
            }
        },
        processScreen: {
            path: 'processScreen',
            component: 'swag-migration-process-screen',
            meta: {
                parentPath: 'sw.settings.index'
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
                profileInstallation: {
                    path: 'profile/installation',
                    component: 'swag-migration-wizard-page-profile-installation'
                },
                connectionCreate: {
                    path: 'connection/create',
                    component: 'swag-migration-wizard-page-connection-create'
                },
                connectionSelect: {
                    path: 'connection/select',
                    component: 'swag-migration-wizard-page-connection-select'
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
    }
});
