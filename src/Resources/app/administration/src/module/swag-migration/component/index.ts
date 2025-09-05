/**
 * @sw-package fundamentals@after-sales
 * @private
 */
import './card';
import './loading-screen';

Shopware.Component.register('swag-migration-dashboard-card', () => import('./swag-migration-dashboard-card'));
Shopware.Component.register('swag-migration-expand-div', () => import('./swag-migration-expand-div'));
Shopware.Component.extend('swag-migration-grid-extended', 'sw-grid', () => import('./swag-migration-grid-extended'));
Shopware.Component.register('swag-migration-grid-selection', () => import('./swag-migration-grid-selection'));
Shopware.Component.register('swag-migration-settings-icon', () => import('./swag-migration-settings-icon'));
Shopware.Component.register('swag-migration-tab-card', () => import('./swag-migration-tab-card'));
Shopware.Component.register('swag-migration-tab-card-item', () => import('./swag-migration-tab-card-item'));
