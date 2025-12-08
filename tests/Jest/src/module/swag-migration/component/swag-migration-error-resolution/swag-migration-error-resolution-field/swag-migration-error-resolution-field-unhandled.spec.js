/**
 * @sw-package after-sales
 */
import { mount } from '@vue/test-utils';
import SwagMigrationErrorResolutionFieldUnhandled from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field-unhandled';

Shopware.Component.register(
    'swag-migration-error-resolution-field-unhandled',
    () => SwagMigrationErrorResolutionFieldUnhandled,
);

const updateFieldValueMock = jest.fn();

const defaultProps = {
    fieldName: 'password',
    disabled: false,
};

async function createWrapper(props = defaultProps) {
    return mount(await Shopware.Component.build('swag-migration-error-resolution-field-unhandled'), {
        props,
        global: {
            stubs: {
                'sw-code-editor': await wrapTestComponent('sw-code-editor'),
                'sw-base-field': await wrapTestComponent('sw-base-field'),
                'sw-inheritance-switch': true,
                'sw-ai-copilot-badge': true,
                'sw-field-error': true,
                'sw-circle-icon': true,
                'sw-help-text': true,
                'mt-banner': true,
            },
            provide: {
                updateFieldValue: updateFieldValueMock,
                userInputSanitizeService: {},
            },
        },
    });
}

describe('src/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field-unhandled', () => {
    beforeAll(() => {
        Shopware.Context.app.config.settings = {
            enableHtmlSanitizer: false,
        };
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    it('should render warning banner and code editor', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('mt-banner-stub').exists()).toBe(true);
        expect(wrapper.find('mt-banner-stub').attributes('variant')).toBe('attention');
        expect(wrapper.find('.sw-code-editor').exists()).toBe(true);
    });

    it('should display fieldName as label', async () => {
        const wrapper = await createWrapper({
            fieldName: 'label-test',
        });
        await flushPromises();

        expect(wrapper.find('.sw-code-editor label').text()).toBe('label-test');
    });

    it('should have default value and call updateFieldValue on mount', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.sw-code-editor__editor').attributes('content')).toBe('');
        expect(updateFieldValueMock).toHaveBeenCalledWith('');
    });

    it.each([
        { name: 'string', value: '"test string"', expected: 'test string' },
        { name: 'number', value: '123', expected: 123 },
        { name: 'boolean', value: 'true', expected: true },
        { name: 'null', value: 'null', expected: null },
        { name: 'object', value: '{"key": "value"}', expected: { key: 'value' } },
        {
            name: 'array',
            value: '[1, 2, 3]',
            expected: [
                1,
                2,
                3,
            ],
        },
    ])('should parse and publish JSON values: $name', async ({ value, expected }) => {
        const wrapper = await createWrapper();
        await flushPromises();
        jest.clearAllMocks();

        await wrapper.setData({ fieldValue: value });
        await flushPromises();

        expect(updateFieldValueMock).toHaveBeenCalledWith(expected);
    });

    it.each([
        {
            name: 'trailing comma in object',
            value: '{"key": "value",}',
        },
        {
            name: 'trailing comma in array',
            value: '[1, 2, 3,]',
        },
        {
            name: 'nested trailing commas',
            value: '{"outer": {"inner": "value",},}',
        },
    ])('should return null and set error for trailing commas: $name', async ({ value }) => {
        const wrapper = await createWrapper();
        await flushPromises();
        jest.clearAllMocks();

        await wrapper.setData({ fieldValue: value });
        await flushPromises();

        expect(updateFieldValueMock).toHaveBeenCalledWith(null);
        expect(wrapper.vm.error).not.toBeNull();
        expect(wrapper.vm.error.detail).toBeDefined();
    });

    it.each([
        {
            name: 'invalid JSON',
            value: 'not json',
        },
        {
            name: 'incomplete object',
            value: '{"key": ',
        },
        {
            name: 'single quotes',
            value: "{'key': 'value'}",
        },
    ])('should return null and set error for invalid JSON: $name', async ({ value }) => {
        const wrapper = await createWrapper();
        await flushPromises();
        jest.clearAllMocks();

        await wrapper.setData({ fieldValue: value });
        await flushPromises();

        expect(updateFieldValueMock).toHaveBeenCalledWith(null);
        expect(wrapper.vm.error).not.toBeNull();
        expect(wrapper.vm.error.detail).toBeDefined();
    });

    it('should trim whitespace from JSON string', async () => {
        const wrapper = await createWrapper();
        await flushPromises();
        jest.clearAllMocks();

        await wrapper.setData({ fieldValue: '  {"key": "value"}  ' });
        await flushPromises();

        expect(updateFieldValueMock).toHaveBeenCalledWith({ key: 'value' });
        expect(wrapper.vm.error).toBeNull();
    });

    it('should clear error when valid JSON is entered after invalid JSON', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        await wrapper.setData({ fieldValue: 'invalid json' });
        await flushPromises();
        expect(wrapper.vm.error).not.toBeNull();

        jest.clearAllMocks();
        await wrapper.setData({ fieldValue: '{"valid": "json"}' });
        await flushPromises();

        expect(wrapper.vm.error).toBeNull();
        expect(updateFieldValueMock).toHaveBeenCalledWith({ valid: 'json' });
    });
});
