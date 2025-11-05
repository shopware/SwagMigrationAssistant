/**
 * @sw-package fundamentals@after-sales
 * @private
 */
Shopware.Component.register(
    'swag-migration-error-resolution-field',
    () => import('./swag-migration-error-resolution-field'),
);
Shopware.Component.register(
    'swag-migration-error-resolution-field-scalar',
    () => import('./swag-migration-error-resolution-field-scalar'),
);
Shopware.Component.register(
    'swag-migration-error-resolution-field-relation',
    () => import('./swag-migration-error-resolution-field-relation'),
);
Shopware.Component.register(
    'swag-migration-error-resolution-field-unhandled',
    () => import('./swag-migration-error-resolution-field-unhandled'),
);
