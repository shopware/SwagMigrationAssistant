/**
 * @sw-package fundamentals@after-sales
 */
describe('src/module/swag-migration/index', () => {
    let registerCallCount;
    let extendCallCount;
    let overrideCallCount;

    beforeAll(async () => {
        const registerSpy = jest.spyOn(Shopware.Component, 'register');
        const extendSpy = jest.spyOn(Shopware.Component, 'extend');
        const overrideSpy = jest.spyOn(Shopware.Component, 'override');

        await import('SwagMigrationAssistant/module/swag-migration/index');

        registerCallCount = registerSpy.mock.calls.length;
        extendCallCount = extendSpy.mock.calls.length;
        overrideCallCount = overrideSpy.mock.calls.length;
    });

    it('should register mixin', async () => {
        expect(Shopware.Mixin.getByName('swag-wizard')).toBeDefined();
    });

    it('should register store', async () => {
        expect(Shopware.Store.get('swagMigration')).toBeDefined();
    });

    it('should register module', () => {
        const module = Shopware.Module.getModuleRegistry().get('swag-migration');
        expect(module).toBeDefined();

        expect(module.manifest).toEqual({
            type: 'plugin',
            name: 'swag-migration',
            title: 'swag-migration.general.mainMenuItemGeneral',
            description: 'swag-migration.general.descriptionTextModule',
            color: '#9AA8B5',
            icon: 'regular-cog',
            version: '0.9.0',
            targetVersion: '0.9.0',
            routes: expect.any(Object),
            display: true,
            settingsItem: [
                {
                    id: 'swag-migration',
                    name: 'swag-migration',
                    label: 'swag-migration.general.mainMenuItemGeneral',
                    group: 'plugins',
                    to: 'swag.migration.index',
                    iconComponent: 'swag-migration-settings-icon',
                    privilege: 'admin',
                },
            ],
        });
    });

    it('should register module routes', async () => {
        const routes = {
            'swag.migration.index.main': {
                path: '/swag/migration/index/main',
                component: 'swag-migration-main-page',
            },
            'swag.migration.index.resetMigration': {
                path: '/swag/migration/index/reset-migration',
                component: 'swag-migration-main-page',
            },
            'swag.migration.index.history.detail': {
                path: '/swag/migration/index/history/detail/:id',
                component: 'swag-migration-history-detail',
            },
            'swag.migration.index.history': {
                path: '/swag/migration/index/history',
                component: 'swag-migration-history',
            },
            'swag.migration.index.dataSelector': {
                path: '/swag/migration/index/dataSelector',
                component: 'swag-migration-data-selector',
            },
            'swag.migration.processScreen': {
                path: '/swag/migration/processScreen',
                components: { default: 'swag-migration-process-screen' },
            },
            'swag.migration.index': {
                path: '/swag/migration/index',
                components: { default: 'swag-migration-index' },
            },
            'swag.migration.wizard.introduction': {
                path: '/swag/migration/wizard/introduction',
                component: 'swag-migration-wizard-page-introduction',
            },
            'swag.migration.wizard.profileInstallation': {
                path: '/swag/migration/wizard/profile/installation',
                component: 'swag-migration-wizard-page-profile-installation',
            },
            'swag.migration.wizard.connectionCreate': {
                path: '/swag/migration/wizard/connection/create',
                component: 'swag-migration-wizard-page-connection-create',
            },
            'swag.migration.wizard.connectionSelect': {
                path: '/swag/migration/wizard/connection/select',
                component: 'swag-migration-wizard-page-connection-select',
            },
            'swag.migration.wizard.credentials': {
                path: '/swag/migration/wizard/credentials',
                component: 'swag-migration-wizard-page-credentials',
            },
            'swag.migration.wizard.credentialsSuccess': {
                path: '/swag/migration/wizard/credentials/success',
                component: 'swag-migration-wizard-page-credentials-success',
            },
            'swag.migration.wizard.credentialsError': {
                path: '/swag/migration/wizard/credentials/error',
                component: 'swag-migration-wizard-page-credentials-error',
            },
            'swag.migration.wizard': {
                path: '/swag/migration/wizard',
                components: { default: 'swag-migration-wizard' },
            },
        };

        const register = Shopware.Module.getModuleRegistry().get('swag-migration').routes;
        expect(register).toBeDefined();

        expect(register.size).toBe(Object.keys(routes).length);
        Object.keys(routes).forEach((name) => {
            const route = register.get(name);

            expect(route.path).toBe(routes[name].path);
            expect(route.component).toBe(routes[name].component);
            expect(route.components).toStrictEqual(routes[name].components);
        });
    });

    it('should register components', async () => {
        const components = [
            'swag-migration-shop-information',
            'swag-migration-premapping',
            'swag-migration-progress-bar',
            'swag-migration-assistant',
            'swag-migration-confirm-warning',
            'swag-migration-loading-screen',
            'swag-migration-result-screen',
            'swag-migration-dashboard-card',
            'swag-migration-grid-extended',
            'swag-migration-grid-selection',
            'swag-migration-settings-icon',
            'swag-migration-tab-card',
            'swag-migration-tab-card-item',
            'swag-migration-wizard',
            'swag-migration-wizard-page-connection-create',
            'swag-migration-wizard-page-connection-select',
            'swag-migration-wizard-page-credentials',
            'swag-migration-wizard-page-credentials-error',
            'swag-migration-wizard-page-credentials-success',
            'swag-migration-wizard-page-introduction',
            'swag-migration-wizard-page-profile-installation',
            'swag-migration-base',
            'swag-migration-data-selector',
            'swag-migration-history',
            'swag-migration-history-detail',
            'swag-migration-history-detail-data',
            'swag-migration-history-detail-errors',
            'swag-migration-index',
            'swag-migration-main-page',
            'swag-migration-process-screen',
            'swag-migration-profile-shopware-api-credential-form',
            'swag-migration-profile-shopware-local-credential-form',
            'swag-migration-profile-shopware6major-api-credential-form',
            'swag-migration-profile-shopware54-api-credential-form',
            'swag-migration-profile-shopware54-api-page-information',
            'swag-migration-profile-shopware54-local-credential-form',
            'swag-migration-profile-shopware55-api-credential-form',
            'swag-migration-profile-shopware55-api-page-information',
            'swag-migration-profile-shopware55-local-credential-form',
            'swag-migration-profile-shopware56-api-credential-form',
            'swag-migration-profile-shopware56-api-page-information',
            'swag-migration-profile-shopware56-local-credential-form',
            'swag-migration-profile-shopware57-api-credential-form',
            'swag-migration-profile-shopware57-api-page-information',
            'swag-migration-profile-shopware57-local-credential-form',
        ];

        const register = Shopware.Component.getComponentRegistry();

        expect(register.size).toBe(components.length);
        components.forEach((component) => {
            expect(register.get(component)).toBeDefined();
        });

        expect(registerCallCount).toBe(30);
        expect(extendCallCount).toBe(15);
        expect(overrideCallCount).toBe(1);
    });
});
