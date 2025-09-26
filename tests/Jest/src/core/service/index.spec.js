import MigrationApiService from 'SwagMigrationAssistant/core/service/api/swag-migration.api.service';

/**
 * @sw-package fundamentals@after-sales
 */
describe('src/core/service/index', () => {
    it('should register migration api service', async () => {
        const serviceSpy = jest.spyOn(Shopware.Application, 'addServiceProvider');
        await import('SwagMigrationAssistant/core/service/index.ts');

        expect(serviceSpy).toHaveBeenCalledWith('migrationApiService', expect.any(Function));
        expect(Shopware.Application.getContainer('service').migrationApiService).toBeInstanceOf(MigrationApiService);
    });
});
