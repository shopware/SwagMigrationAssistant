const MIGRATION_ACL_KEY = 'swag_migration';

const acl = {
    category: 'permissions',
    parent: 'settings',
    key: MIGRATION_ACL_KEY,
    roles: {
        viewer: {
            privileges: [
                'swag_migration_connection:read',
                'swag_migration_data:read',
                'swag_migration_fix:read',
                'swag_migration_general_setting:read',
                'swag_migration_logging:read',
                'swag_migration_mapping:read',
                'swag_migration_media_file:read',
                'swag_migration_run:read',
                'swag_migration_history:read',
                'system_config:read',
            ],
            dependencies: [],
        },
        editor: {
            privileges: [
                'swag_migration_connection:update',
                'swag_migration_data:update',
                'swag_migration_fix:update',
                'swag_migration_general_setting:update',
                'swag_migration_logging:update',
                'swag_migration_mapping:update',
                'swag_migration_media_file:update',
                'swag_migration_run:update',
                'swag_migration_history:update',
            ],
            dependencies: [
                'swag_migration.viewer',
            ],
        },
        creator: {
            privileges: [
                'swag_migration_connection:create',
                'swag_migration_data:create',
                'swag_migration_fix:create',
                'swag_migration_general_setting:create',
                'swag_migration_logging:create',
                'swag_migration_mapping:create',
                'swag_migration_media_file:create',
                'swag_migration_run:create',
                'swag_migration_history:create',
            ],
            dependencies: [
                'swag_migration.viewer',
                'swag_migration.editor',
            ],
        },
        deleter: {
            privileges: [
                'swag_migration_connection:delete',
                'swag_migration_data:delete',
                'swag_migration_fix:delete',
                'swag_migration_general_setting:delete',
                'swag_migration_logging:delete',
                'swag_migration_mapping:delete',
                'swag_migration_media_file:delete',
                'swag_migration_run:delete',
                'swag_migration_history:delete',
            ],
            dependencies: [
                'swag_migration.viewer',
            ],
        },
    },
};

Shopware.Service('privileges').addPrivilegeMappingEntry(acl);

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export { MIGRATION_ACL_KEY, acl };
