/**
 * @sw-package after-sales
 */

const aclRegisterMock = jest.fn();

const componentRegisterMock = jest.fn();
const componentExtendMock = jest.fn();
const componentOverrideMock = jest.fn();
const mixinRegisterMock = jest.fn();
const serviceRegisterMock = jest.fn();
const storeRegisterMock = jest.fn();
const moduleRegisterMock = jest.fn();

const originalShopware = Shopware;

describe('src/module/swag-migration/index', () => {
    beforeAll(() => {
        Shopware = {
            ...originalShopware,
            Module: {
                register: moduleRegisterMock,
            },
            Application: {
                addServiceProvider: serviceRegisterMock,
            },
            Store: {
                register: storeRegisterMock,
            },
            Component: {
                register: componentRegisterMock,
                extend: componentExtendMock,
                override: componentOverrideMock,
            },
            Mixin: {
                register: mixinRegisterMock,
            },
            Service: () => {
                return {
                    addPrivilegeMappingEntry: aclRegisterMock,
                    create: jest.fn(),
                };
            },
        };
    });

    beforeEach(async () => {
        jest.resetAllMocks();
        jest.resetModules();

        await import('SwagMigrationAssistant/module/swag-migration/index');
    });

    afterAll(() => {
        Shopware = originalShopware;
    });

    it('should register mixin', async () => {
        expect(mixinRegisterMock).toHaveBeenCalledTimes(1);

        expect(mixinRegisterMock).toHaveBeenCalledWith('swag-wizard', expect.any(Object));
    });

    it('should register acl', async () => {
        expect(aclRegisterMock).toHaveBeenCalledTimes(1);

        expect(aclRegisterMock).toHaveBeenCalledWith({
            category: 'permissions',
            parent: 'settings',
            key: 'swag_migration',
            roles: expect.any(Object),
        });
    });

    it('should register store', async () => {
        expect(storeRegisterMock).toHaveBeenCalledTimes(1);

        expect(storeRegisterMock).toHaveBeenCalledWith({
            id: 'swagMigration',
            state: expect.any(Function),
            actions: expect.any(Object),
            getters: expect.any(Object),
        });
    });

    it('should register module', () => {
        expect(moduleRegisterMock).toHaveBeenCalledTimes(1);

        expect(moduleRegisterMock).toHaveBeenCalledWith('swag-migration', {
            type: 'plugin',
            name: 'swag-migration',
            title: 'swag-migration.general.mainMenuItemGeneral',
            description: 'swag-migration.general.descriptionTextModule',
            color: '#9AA8B5',
            icon: 'regular-cog',
            routes: expect.any(Object),
            settingsItem: {
                group: 'plugins',
                to: 'swag.migration.index',
                iconComponent: 'swag-migration-settings-icon',
                privilege: 'swag_migration.viewer',
            },
        });
    });

    it('should register, extend & override components', async () => {
        const componentsRegistered = [
            'swag-migration-shop-information',
            'swag-migration-premapping',
            'swag-migration-progress-bar',
            'swag-migration-confirm-warning',
            'swag-migration-loading-screen',
            'swag-migration-result-screen',
            'swag-migration-error-resolution-field',
            'swag-migration-error-resolution-field-scalar',
            'swag-migration-error-resolution-field-relation',
            'swag-migration-error-resolution-field-unhandled',
            'swag-migration-error-resolution-details-modal',
            'swag-migration-error-resolution-modal',
            'swag-migration-error-resolution-log-filter',
            'swag-migration-error-resolution-step',
            'swag-migration-dashboard-card',
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
            'swag-migration-main-page',
            'swag-migration-profile-shopware-api-credential-form',
            'swag-migration-profile-shopware-local-credential-form',
            'swag-migration-profile-shopware6major-api-credential-form',
        ];

        const componentsExtended = [
            'swag-migration-grid-extended',
            'swag-migration-index',
            'swag-migration-process-screen',
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

        const componentsOverridden = [
            'sw-dashboard-index',
        ];

        expect(componentRegisterMock).toHaveBeenCalledTimes(componentsRegistered.length);
        expect(componentExtendMock).toHaveBeenCalledTimes(componentsExtended.length);
        expect(componentOverrideMock).toHaveBeenCalledTimes(1);

        componentsRegistered.forEach((component) => {
            expect(componentRegisterMock).toHaveBeenCalledWith(component, expect.any(Function));
        });

        componentsExtended.forEach((component) => {
            expect(componentExtendMock).toHaveBeenCalledWith(component, expect.any(String), expect.anything());
        });

        componentsOverridden.forEach((component) => {
            expect(componentOverrideMock).toHaveBeenCalledWith(component, expect.any(Function));
        });
    });
});
