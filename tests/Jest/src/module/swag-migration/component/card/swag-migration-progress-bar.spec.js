import { mount } from '@vue/test-utils';
import swagMigrationProgressBar from 'SwagMigrationAssistant/module/swag-migration/component/card/swag-migration-progress-bar';

Shopware.Component.register('swag-migration-progress-bar', swagMigrationProgressBar);

async function createWrapper(props = {}) {
    const wrapper = mount(
        await Shopware.Component.build('swag-migration-progress-bar'),
        {
            props,
            global: {
                stubs: {
                },
            },
        },
    );
    await flushPromises();
    return wrapper;
}

describe('module/swag-migration/component/card/swag-migration-progress-bar', () => {
    it('should have disabled right point class', async () => {
        const wrapper = await createWrapper({
            value: 50,
            maxValue: 100,
        });

        const rightPoint = wrapper.find('.swag-migration-progress-bar__right-point .swag-migration-progress-bar__bubble');
        expect(rightPoint.classes()).toContain('swag-migration-progress-bar__bubble--disabled');
    });

    it('should have active right point class', async () => {
        const wrapper = await createWrapper({
            value: 100,
            maxValue: 100,
        });

        const rightPoint = wrapper.find('.swag-migration-progress-bar__right-point .swag-migration-progress-bar__bubble');
        expect(rightPoint.classes()).toContain('swag-migration-progress-bar__bubble--active');
    });
});
