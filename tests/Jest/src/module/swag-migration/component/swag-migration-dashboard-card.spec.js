/**
 * @sw-package fundamentals@after-sales
 */
import { mount } from '@vue/test-utils';
import swagMigrationDashboardCard from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-dashboard-card';

Shopware.Component.register('swag-migration-dashboard-card', swagMigrationDashboardCard);

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-migration-dashboard-card'), {
        global: {
            stubs: {
                'sw-container': await wrapTestComponent('sw-container'),
            },
        },
    });
}

describe('src/module/swag-migration/component/swag-migration-dashboard-card', () => {
    it('should have correct image path', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.swag-migration-dashboard-card__right-content img').attributes('src')).toBe(
            'swagmigrationassistant/administration/static/img/wizard-introduction.svg',
        );
    });

    it('should redirect to migration index page', async () => {
        const wrapper = await createWrapper();
        wrapper.vm.$router.push = jest.fn();
        await flushPromises();

        await wrapper.find('.swag-migration-dashboard-card__button').trigger('click');
        expect(wrapper.vm.$router.push).toHaveBeenNthCalledWith(1, { name: 'swag.migration.index' });
    });
});
