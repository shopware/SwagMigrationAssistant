/**
 * @sw-package fundamentals@after-sales
 * @private
 */
import './wizard';

Shopware.Component.register('swag-migration-base', () => import('./swag-migration-base'));
Shopware.Component.register('swag-migration-data-selector', () => import('./swag-migration-data-selector'));
Shopware.Component.register('swag-migration-history', () => import('./swag-migration-history'));
Shopware.Component.register('swag-migration-history-detail', () => import('./swag-migration-history-detail'));
Shopware.Component.register('swag-migration-history-detail-data', () => import('./swag-migration-history-detail-data'));
Shopware.Component.register('swag-migration-history-detail-errors', () => import('./swag-migration-history-detail-errors'));
Shopware.Component.extend('swag-migration-index', 'swag-migration-base', () => import('./swag-migration-index'));
Shopware.Component.register('swag-migration-main-page', () => import('./swag-migration-main-page'));
Shopware.Component.extend(
    'swag-migration-process-screen',
    'swag-migration-base',
    () => import('./swag-migration-process-screen'),
);
