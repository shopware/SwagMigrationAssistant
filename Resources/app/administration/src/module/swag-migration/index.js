import './mixin';
import './component';
import './page';
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
                parentPath: 'sw.settings.index',
                privilege: 'admin',
            },
        },
        index: {
            component: 'swag-migration-index',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
            },
            redirect: { name: 'swag.migration.index.main' },
            children: {
                main: {
                    path: 'main',
                    component: 'swag-migration-main-page',
                    meta: {
                        parentPath: 'sw.settings.index',
                        resetMigration: false,
                        privilege: 'admin',
                    },
                },
                resetMigration: {
                    path: 'reset-migration',
                    component: 'swag-migration-main-page',
                    meta: {
                        parentPath: 'sw.settings.index',
                        resetMigration: true,
                        privilege: 'admin',
                    },
                },
                history: {
                    path: 'history',
                    component: 'swag-migration-history',
                    children: {
                        detail: {
                            path: 'detail/:id',
                            component: 'swag-migration-history-detail',
                            meta: {
                                parentPath: 'sw.settings.index',
                                privilege: 'admin',
                            },
                        },
                    },
                    meta: {
                        parentPath: 'sw.settings.index',
                        privilege: 'admin',
                    },
                },
                dataSelector: {
                    path: 'dataSelector',
                    component: 'swag-migration-data-selector',
                    meta: {
                        parentPath: 'sw.settings.index',
                        privilege: 'admin',
                    },
                },
            },
        },
        processScreen: {
            path: 'processScreen',
            component: 'swag-migration-process-screen',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'admin',
            },
        },
        wizard: {
            component: 'swag-migration-wizard',
            path: 'wizard',
            redirect: { name: 'swag.migration.wizard.introduction' },
            children: {
                introduction: {
                    path: 'introduction',
                    component: 'swag-migration-wizard-page-introduction',
                    meta: {
                        privilege: 'admin',
                    },
                },
                profileInstallation: {
                    path: 'profile/installation',
                    component: 'swag-migration-wizard-page-profile-installation',
                    meta: {
                        privilege: 'admin',
                    },
                },
                connectionCreate: {
                    path: 'connection/create',
                    component: 'swag-migration-wizard-page-connection-create',
                    meta: {
                        privilege: 'admin',
                    },
                },
                connectionSelect: {
                    path: 'connection/select',
                    component: 'swag-migration-wizard-page-connection-select',
                    meta: {
                        privilege: 'admin',
                    },
                },
                profileInformation: {
                    path: 'profile/information',
                    component: 'swag-migration-wizard-page-profile-information',
                    meta: {
                        privilege: 'admin',
                    },
                },
                credentials: {
                    path: 'credentials',
                    component: 'swag-migration-wizard-page-credentials',
                    meta: {
                        privilege: 'admin',
                    },
                },
                credentialsSuccess: {
                    path: 'credentials/success',
                    component: 'swag-migration-wizard-page-credentials-success',
                    meta: {
                        privilege: 'admin',
                    },
                },
                credentialsError: {
                    path: 'credentials/error',
                    component: 'swag-migration-wizard-page-credentials-error',
                    meta: {
                        privilege: 'admin',
                    },
                },
            },
            meta: {
                privilege: 'admin',
            },
        },
    },

    settingsItem: {
        group: 'plugins',
        to: 'swag.migration.index',
        iconComponent: 'swag-migration-settings-icon',
        privilege: 'admin',
    },
});
