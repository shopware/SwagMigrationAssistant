/**
 * @sw-package after-sales
 */
import { mount } from '@vue/test-utils';
import SwagMigrationErrorResolutionDetailsModal from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-details-modal';

Shopware.Component.register('swag-migration-error-resolution-details-modal', () => SwagMigrationErrorResolutionDetailsModal);

const defaultProps = {
    entityName: 'customer',
    selectedLog: {
        status: false,
        entityId: 'test-id',
        convertedData: null,
        sourceData: null,
    },
};

async function createWrapper(props = defaultProps) {
    return mount(await Shopware.Component.build('swag-migration-error-resolution-details-modal'), {
        props,
        global: {
            stubs: {
                'sw-modal': await wrapTestComponent('sw-modal'),
                'sw-code-editor': true,
                'sw-loader': true,
            },
            provide: {
                shortcutService: {
                    stopEventListener: () => {},
                    startEventListener: () => {},
                },
            },
        },
    });
}

describe('src/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-details-modal', () => {
    it('should emit close event on close', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.sw-modal__close').exists()).toBe(true);
        await wrapper.find('.sw-modal__close').trigger('click');
        await flushPromises();

        expect(wrapper.emitted('modal-close')).toBeDefined();
    });

    it.each([
        { name: 'source-data', value: { sourceData: { id: 'test-source-data' } } },
        { name: 'converted-data', value: { convertedData: { id: 'test-converted-data' } } },
    ])('should display data in editor if filled: $name', async ({ name, value }) => {
        const wrapper = await createWrapper({
            ...defaultProps,
            selectedLog: {
                ...defaultProps.selectedLog,
                ...value,
            },
        });
        await flushPromises();

        expect(wrapper.find(`.swag-migration-error-resolution-details-modal__code-editor-${name}`).exists()).toBe(true);
        expect(wrapper.find(`.swag-migration-error-resolution-details-modal__code-editor-${name}`).attributes('value')).toBe(
            JSON.stringify(value[Object.keys(value).at(0)], null, 2),
        );
    });

    it.each([
        { name: 'source-data', value: { sourceData: {} } },
        { name: 'converted-data', value: { convertedData: {} } },
    ])('should not display data in editor if empty object: $name', async ({ name, value }) => {
        const wrapper = await createWrapper({
            ...defaultProps,
            selectedLog: {
                ...defaultProps.selectedLog,
                ...value,
            },
        });
        await flushPromises();

        expect(wrapper.find(`.swag-migration-error-resolution-details-modal__code-editor-${name}`).exists()).toBe(false);
    });
});
