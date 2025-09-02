/**
 * @sw-package fundamentals@after-sales
 * @private
 */
Shopware.Component.register('swag-migration-wizard', () => import('./swag-migration-wizard'));
Shopware.Component.register(
    'swag-migration-wizard-page-connection-create',
    () => import('./swag-migration-wizard-page-connection-create'),
);
Shopware.Component.register(
    'swag-migration-wizard-page-connection-select',
    () => import('./swag-migration-wizard-page-connection-select'),
);
Shopware.Component.register(
    'swag-migration-wizard-page-credentials',
    () => import('./swag-migration-wizard-page-credentials'),
);
Shopware.Component.register(
    'swag-migration-wizard-page-credentials-error',
    () => import('./swag-migration-wizard-page-credentials-error'),
);
Shopware.Component.register(
    'swag-migration-wizard-page-credentials-success',
    () => import('./swag-migration-wizard-page-credentials-success'),
);
Shopware.Component.register(
    'swag-migration-wizard-page-introduction',
    () => import('./swag-migration-wizard-page-introduction'),
);
Shopware.Component.register(
    'swag-migration-wizard-page-profile-installation',
    () => import('./swag-migration-wizard-page-profile-installation'),
);
