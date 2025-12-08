/**
 * @sw-package after-sales
 */
import { MIGRATION_API_SERVICE } from 'SwagMigrationAssistant/core/service/api/swag-migration.api.service';
import { MIGRATION_ERROR_RESOLUTION_SERVICE } from 'SwagMigrationAssistant/module/swag-migration/service/swag-migration-error-resolution.service';

const addPrivilegeMappingEntryMock = jest.fn();
const addServiceProviderMock = jest.fn();
const registerModuleMock = jest.fn();
const extendLocaleMock = jest.fn();

const originalShopware = Shopware;

describe('src/main', () => {
    beforeAll(() => {
        Shopware = {
            ...originalShopware,
            Module: {
                register: registerModuleMock,
            },
            Service: () => {
                return {
                    addPrivilegeMappingEntry: addPrivilegeMappingEntryMock,
                    create: jest.fn(),
                };
            },
            Application: {
                addServiceProvider: addServiceProviderMock,
            },
            Locale: {
                extend: extendLocaleMock,
            },
        };
    });

    beforeEach(async () => {
        jest.resetAllMocks();
        jest.resetModules();

        await import('SwagMigrationAssistant/main');
    });

    afterAll(() => {
        Shopware = originalShopware;
    });

    it('should load core services, modules & snippets', async () => {
        expect(addServiceProviderMock).toHaveBeenCalledTimes(2);
        expect(addServiceProviderMock).toHaveBeenNthCalledWith(1, MIGRATION_API_SERVICE, expect.any(Function));
        expect(addServiceProviderMock).toHaveBeenNthCalledWith(2, MIGRATION_ERROR_RESOLUTION_SERVICE, expect.any(Function));

        expect(registerModuleMock).toHaveBeenCalledTimes(1);
        expect(registerModuleMock).toHaveBeenCalledWith('swag-migration', expect.any(Object));

        expect(extendLocaleMock).toHaveBeenCalledTimes(2);
        expect(extendLocaleMock).toHaveBeenNthCalledWith(1, 'de-DE', expect.any(Object));
        expect(extendLocaleMock).toHaveBeenNthCalledWith(2, 'en-GB', expect.any(Object));
    });
});
