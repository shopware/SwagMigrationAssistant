/**
 * @sw-package after-sales
 */
import { MIGRATION_ACL_KEY, acl } from 'SwagMigrationAssistant/module/swag-migration/acl';

const aclRegisterMock = jest.fn();

const originalShopware = Shopware;

describe('src/module/swag-migration/acl/index', () => {
    beforeAll(() => {
        Shopware = {
            ...originalShopware,
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

        await import('SwagMigrationAssistant/module/swag-migration/acl/index');
    });

    afterAll(() => {
        Shopware = originalShopware;
    });

    it('should register acl privileges', async () => {
        expect(MIGRATION_ACL_KEY).toBeDefined();
        expect(acl).toBeDefined();

        expect(aclRegisterMock).toHaveBeenCalledTimes(1);
        expect(aclRegisterMock).toHaveBeenCalledWith({
            category: 'permissions',
            parent: 'settings',
            key: 'swag_migration',
            roles: {
                viewer: expect.any(Object),
                editor: expect.any(Object),
                creator: expect.any(Object),
                deleter: expect.any(Object),
            },
        });
    });
});
