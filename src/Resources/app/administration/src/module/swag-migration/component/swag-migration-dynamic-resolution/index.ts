/**
 * @sw-package fundamentals@after-sales
 * @private
 */
Shopware.Component.register(
    'swag-migration-dynamic-resolution-field',
    () => import('./swag-migration-dynamic-resolution-field'),
);
Shopware.Component.register(
    'swag-migration-dynamic-resolution-field-scalar',
    () => import('./swag-migration-dynamic-resolution-field-scalar'),
);
Shopware.Component.register(
    'swag-migration-dynamic-resolution-field-relation',
    () => import('./swag-migration-dynamic-resolution-field-relation'),
);
