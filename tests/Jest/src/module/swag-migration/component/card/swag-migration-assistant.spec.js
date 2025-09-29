/**
 * @sw-package fundamentals@after-sales
 */
import { mount } from '@vue/test-utils';
import swagMigrationAssistant from 'SwagMigrationAssistant/module/swag-migration/component/card/swag-migration-assistant';

Shopware.Component.register('swag-migration-assistant', swagMigrationAssistant);

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-migration-assistant'));
}

describe('src/module/swag-migration/component/card/swag-migration-assistant', () => {
    it('should have correct image paths', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.swag-migration-assistant__illustration-connection').attributes('src')).toBe(
            'swagmigrationassistant/administration/static/img/assistant-connection.svg',
        );
        expect(wrapper.find('.swag-migration-assistant__illustration-dataSelection').attributes('src')).toBe(
            'swagmigrationassistant/administration/static/img/assistant-dataSelection.svg',
        );
        expect(wrapper.find('.swag-migration-assistant__illustration-migrationOverview').attributes('src')).toBe(
            'swagmigrationassistant/administration/static/img/assistant-migrationOverview.svg',
        );
    });

    it('should redirect to documentation', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        await wrapper.find('.swag-migration-assistant__link a').trigger('click');
        expect(wrapper.emitted('click')).toBeDefined();
    });
});
