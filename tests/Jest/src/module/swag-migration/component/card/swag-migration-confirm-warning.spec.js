/**
 * @sw-package fundamentals@after-sales
 */
import { mount } from '@vue/test-utils';
import swagMigrationConfirmWarning from 'SwagMigrationAssistant/module/swag-migration/component/card/swag-migration-confirm-warning';
import { MIGRATION_STORE_ID } from 'SwagMigrationAssistant/module/swag-migration/store/migration.store';

Shopware.Component.register('swag-migration-confirm-warning', swagMigrationConfirmWarning);

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-migration-confirm-warning'));
}

describe('src/module/swag-migration/component/card/swag-migration-confirm-warning', () => {
    it.each([
        { expected: true, source: 'EUR', target: 'USD' },
        { expected: false, source: 'EUR', target: 'EUR' },
    ])('should render warning card when currencies differ: $expected', async ({ expected, source, target }) => {
        Shopware.Store.get(MIGRATION_STORE_ID).setEnvironmentInformation({
            sourceSystemCurrency: source,
            targetSystemCurrency: target,
        });

        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.swag-migration-confirm-warning__card').exists()).toBe(expected);
    });

    it.each([
        { expected: true, source: 'DE', target: 'EN' },
        { expected: false, source: 'DE', target: 'DE' },
    ])('should render warning card when language differ: $expected', async ({ expected, source, target }) => {
        Shopware.Store.get(MIGRATION_STORE_ID).setEnvironmentInformation({
            sourceSystemLocale: source,
            targetSystemLocale: target,
        });

        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.swag-migration-confirm-warning__card').exists()).toBe(expected);
    });

    it('should update continue checkbox state in store', async () => {
        Shopware.Store.get(MIGRATION_STORE_ID).setEnvironmentInformation({
            sourceSystemCurrency: 'USD',
            targetSystemCurrency: 'EUR',
        });

        const wrapper = await createWrapper();
        await flushPromises();

        await wrapper.find('.swag-migration-confirm-warning__checkbox input').setValue(true);
        expect(Shopware.Store.get(MIGRATION_STORE_ID).warningConfirmed).toBe(true);

        await wrapper.find('.swag-migration-confirm-warning__checkbox input').setValue(false);
        expect(Shopware.Store.get(MIGRATION_STORE_ID).warningConfirmed).toBe(false);
    });
});
