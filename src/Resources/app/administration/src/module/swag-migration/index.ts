import './acl';
import './component';
import './extension';
import './mixin/swag-wizard.mixin';
import './page';
import './profile';
import './service';
import './store/migration.store';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Shopware.Module.register('swag-migration', {
    type: 'plugin',
    name: 'swag-migration',
    title: 'swag-migration.general.mainMenuItemGeneral',
    description: 'swag-migration.general.descriptionTextModule',
    color: '#9AA8B5',
    icon: 'regular-cog',

    routes: {
        index: {
            component: 'swag-migration-index',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'swag_migration.viewer',
            },
            redirect: { name: 'swag.migration.index.main' },
            children: {
                main: {
                    path: 'main',
                    component: 'swag-migration-main-page',
                    meta: {
                        parentPath: 'sw.settings.index',
                        resetMigration: false,
                        privilege: 'swag_migration.viewer',
                    },
                },
                resetMigration: {
                    path: 'reset-migration',
                    component: 'swag-migration-main-page',
                    meta: {
                        parentPath: 'sw.settings.index',
                        resetMigration: true,
                        privilege: 'swag_migration.deleter',
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
                                privilege: 'swag_migration.viewer',
                            },
                        },
                    },
                    meta: {
                        parentPath: 'sw.settings.index',
                        privilege: 'swag_migration.viewer',
                    },
                },
                dataSelector: {
                    path: 'dataSelector',
                    component: 'swag-migration-data-selector',
                    meta: {
                        parentPath: 'sw.settings.index',
                        privilege: 'swag_migration.editor',
                    },
                },
            },
        },
        processScreen: {
            path: 'processScreen',
            component: 'swag-migration-process-screen',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'swag_migration.viewer',
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
                        privilege: 'swag_migration.editor',
                    },
                },
                profileInstallation: {
                    path: 'profile/installation',
                    component: 'swag-migration-wizard-page-profile-installation',
                    meta: {
                        privilege: 'swag_migration.editor',
                    },
                },
                connectionCreate: {
                    path: 'connection/create',
                    component: 'swag-migration-wizard-page-connection-create',
                    meta: {
                        privilege: 'swag_migration.creator',
                    },
                },
                connectionSelect: {
                    path: 'connection/select',
                    component: 'swag-migration-wizard-page-connection-select',
                    meta: {
                        privilege: 'swag_migration.editor',
                    },
                },
                credentials: {
                    path: 'credentials',
                    component: 'swag-migration-wizard-page-credentials',
                    meta: {
                        privilege: 'swag_migration.editor',
                    },
                },
                credentialsSuccess: {
                    path: 'credentials/success',
                    component: 'swag-migration-wizard-page-credentials-success',
                    meta: {
                        privilege: 'swag_migration.editor',
                    },
                },
                credentialsError: {
                    path: 'credentials/error',
                    component: 'swag-migration-wizard-page-credentials-error',
                    meta: {
                        privilege: 'swag_migration.editor',
                    },
                },
            },
        },
    },

    settingsItem: {
        group: 'plugins',
        to: 'swag.migration.index',
        iconComponent: 'swag-migration-settings-icon',
        privilege: 'swag_migration.viewer',
    },
});
