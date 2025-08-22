/**
 * @package after-sales
 * @private
 */
Shopware.Component.register(
    'swag-migration-profile-shopware-api-credential-form',
    () => import('./api/swag-migration-profile-shopware-api-credential-form'),
);
Shopware.Component.register(
    'swag-migration-profile-shopware-api-page-information',
    () => import('./api/swag-migration-profile-shopware-api-page-information'),
);
Shopware.Component.register(
    'swag-migration-profile-shopware-local-credential-form',
    () => import('./local/swag-migration-profile-shopware-local-credential-form'),
);
