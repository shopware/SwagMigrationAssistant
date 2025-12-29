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
                'sw-field-error': await wrapTestComponent('sw-field-error'),
                'sw-inheritance-switch': true,
                'sw-ai-copilot-badge': true,
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

        expect(wrapper.find('.sw-code-editor__editor').attributes('content')).toBeUndefined();
        expect(updateFieldValueMock).toHaveBeenCalledWith(null);
    });

    it('should display error message when passed', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.sw-field__error').exists()).toBe(false);

        const message = 'This is an error message';
        await wrapper.setProps({
            error: {
                detail: message,
            },
        });

        expect(wrapper.find('.sw-field__error').exists()).toBe(true);
        expect(wrapper.find('.sw-field__error').text()).toBe(message);
    });

    it('should init with example value when set', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.sw-code-editor__editor').attributes('content')).toBeUndefined();

        const exampleValue = 'example content';
        await wrapper.setProps({
            exampleValue: exampleValue,
        });

        expect(wrapper.find('.sw-code-editor__editor').attributes('content')).toBe(exampleValue);
    });
});
