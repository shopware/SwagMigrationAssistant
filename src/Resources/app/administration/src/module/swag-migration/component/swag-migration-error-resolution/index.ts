/**
 * @sw-package fundamentals@after-sales
 * @private
 */
import './swag-migration-error-resolution-field';

Shopware.Component.register(
    'swag-migration-error-resolution-details-modal',
    () => import('./swag-migration-error-resolution-details-modal'),
);
Shopware.Component.register(
    'swag-migration-error-resolution-modal',
    () => import('./swag-migration-error-resolution-modal'),
);
Shopware.Component.register(
    'swag-migration-error-resolution-log-filter',
    () => import('./swag-migration-error-resolution-log-filter'),
);
Shopware.Component.register('swag-migration-error-resolution-step', () => import('./swag-migration-error-resolution-step'));
