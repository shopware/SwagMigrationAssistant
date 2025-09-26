/**
 * @sw-package fundamentals@after-sales
 */
describe('src/main', () => {
    it('should load core services and modules', async () => {
        const serviceSpy = jest.spyOn(Shopware.Application, 'addServiceProvider');
        const moduleSpy = jest.spyOn(Shopware.Module, 'register');

        await import('SwagMigrationAssistant/main');

        expect(serviceSpy).toHaveBeenCalled();
        expect(moduleSpy).toHaveBeenCalled();
    });
});
