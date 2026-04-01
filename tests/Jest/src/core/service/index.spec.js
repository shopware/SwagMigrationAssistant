/**
 * @sw-package after-sales
 */
import MigrationApiService, {
    MIGRATION_API_SERVICE,
    MIGRATION_STEP,
} from 'SwagMigrationAssistant/core/service/api/swag-migration.api.service';

const addServiceProviderMock = jest.fn();

const originalShopware = Shopware;

describe('src/main', () => {
    beforeAll(() => {
        // eslint-disable-next-line no-global-assign
        Shopware = {
            ...originalShopware,
            Application: {
                addServiceProvider: addServiceProviderMock,
            },
        };
    });

    beforeEach(async () => {
        jest.resetAllMocks();
        jest.resetModules();

        await import('SwagMigrationAssistant/core/service');
    });

    afterAll(() => {
        // eslint-disable-next-line no-global-assign
        Shopware = originalShopware;
    });

    it('should load core services', async () => {
        expect(MIGRATION_API_SERVICE).toBeDefined();
        expect(MIGRATION_STEP).toBeDefined();
        expect(MigrationApiService).toBeDefined();

        expect(addServiceProviderMock).toHaveBeenCalledTimes(1);
        expect(addServiceProviderMock).toHaveBeenNthCalledWith(1, MIGRATION_API_SERVICE, expect.any(Function));
    });
});
