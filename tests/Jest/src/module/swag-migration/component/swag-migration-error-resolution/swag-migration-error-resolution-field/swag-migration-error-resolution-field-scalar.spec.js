/**
 * @sw-package after-sales
 */
import { mount } from '@vue/test-utils';
import SwagMigrationErrorResolutionFieldScalar from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field-scalar';

Shopware.Component.register('swag-migration-error-resolution-field-scalar', () => SwagMigrationErrorResolutionFieldScalar);

const updateFieldValueMock = jest.fn();

const defaultProps = {
    componentType: 'text',
    entityField: {
        entity: 'customer',
        type: 'string',
    },
    fieldName: 'firstName',
    disabled: false,
};

async function createWrapper(props = defaultProps) {
    return mount(await Shopware.Component.build('swag-migration-error-resolution-field-scalar'), {
        props,
        global: {
            stubs: {
                'sw-code-editor': true,
            },
            provide: {
                updateFieldValue: updateFieldValueMock,
            },
        },
    });
}

describe('src/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field-scalar', () => {
    afterEach(() => {
        jest.clearAllMocks();
    });

    it.each([
        { type: 'int', value: 42 },
        { type: 'float', value: 4.2 },
    ])('should display number field: $type', async ({ type, value }) => {
        const props = {
            ...defaultProps,
            componentType: 'number',
            entityField: {
                ...defaultProps.entityField,
                type,
            },
            fieldName: 'orderCount',
        };

        const wrapper = await createWrapper(props);

        expect(wrapper.find('.sw-migration-error-resolution-field__number input').attributes('name')).toBe(
            'migration-resolution--number',
        );
        expect(wrapper.find('.sw-migration-error-resolution-field__number label').text()).toBe(props.fieldName);

        // wrapper check needed, cause meteor handles number types internally
        expect(wrapper.vm.numberFieldType).toBe(type);

        await wrapper.find('.sw-migration-error-resolution-field__number input').setValue(value);
        expect(updateFieldValueMock).toHaveBeenCalledWith(value);

        expect(wrapper.find('.is--disabled').exists()).toBe(false);
        await wrapper.setProps({ disabled: true });
        expect(wrapper.find('.is--disabled').exists()).toBe(true);
    });

    it('should display textarea field', async () => {
        const props = {
            ...defaultProps,
            componentType: 'textarea',
            entityField: {
                entity: 'order',
                type: 'text',
            },
            fieldName: 'customerComment',
        };

        const wrapper = await createWrapper(props);

        expect(wrapper.find('.sw-migration-error-resolution-field__textarea textarea').attributes('name')).toBe(
            'migration-resolution--textarea',
        );
        expect(wrapper.find('.sw-migration-error-resolution-field__textarea label').text()).toBe(props.fieldName);

        const value = 'This is a sample customer comment that needs to be resolved.';
        await wrapper.find('.sw-migration-error-resolution-field__textarea textarea').setValue(value);
        expect(updateFieldValueMock).toHaveBeenCalledWith(value);

        expect(
            wrapper.find('.sw-migration-error-resolution-field__textarea textarea').attributes('disabled'),
        ).not.toBeDefined();
        await wrapper.setProps({ disabled: true });
        expect(wrapper.find('.sw-migration-error-resolution-field__textarea textarea').attributes('disabled')).toBeDefined();
    });

    it('should display text field', async () => {
        const props = {
            ...defaultProps,
            componentType: 'text',
            entityField: {
                entity: 'customer',
                type: 'string',
            },
            fieldName: 'firstName',
        };

        const wrapper = await createWrapper(props);

        expect(wrapper.find('.sw-migration-error-resolution-field__text input').attributes('name')).toBe(
            'migration-resolution--text',
        );
        expect(wrapper.find('.sw-migration-error-resolution-field__text label').text()).toBe(props.fieldName);

        const value = 'John';
        await wrapper.find('.sw-migration-error-resolution-field__text input').setValue(value);
        expect(updateFieldValueMock).toHaveBeenCalledWith(value);

        expect(wrapper.find('.is--disabled').exists()).toBe(false);
        await wrapper.setProps({ disabled: true });
        expect(wrapper.find('.is--disabled').exists()).toBe(true);
    });

    it('should display switch field', async () => {
        const props = {
            ...defaultProps,
            componentType: 'switch',
            entityField: {
                entity: 'customer',
                type: 'bool',
            },
            fieldName: 'active',
        };

        const wrapper = await createWrapper(props);

        expect(wrapper.find('.sw-migration-error-resolution-field__switch input').attributes('name')).toBe(
            'migration-resolution--switch',
        );
        expect(wrapper.find('.sw-migration-error-resolution-field__switch label').text()).toBe(props.fieldName);

        await wrapper.find('.sw-migration-error-resolution-field__switch input').setChecked(true);
        expect(updateFieldValueMock).toHaveBeenCalledWith(true);

        expect(wrapper.find('.sw-migration-error-resolution-field__switch input').attributes('disabled')).not.toBeDefined();
        await wrapper.setProps({ disabled: true });
        expect(wrapper.find('.sw-migration-error-resolution-field__switch input').attributes('disabled')).toBeDefined();
    });

    it('should display datepicker field', async () => {
        const props = {
            ...defaultProps,
            componentType: 'datepicker',
            entityField: {
                entity: 'customer',
                type: 'date',
            },
            fieldName: 'createdAt',
        };

        const wrapper = await createWrapper(props);

        expect(wrapper.find('.sw-migration-error-resolution-field__datepicker').attributes('name')).toBe(
            'migration-resolution--datepicker',
        );
        expect(wrapper.find('.sw-migration-error-resolution-field__datepicker label').text()).toBe(props.fieldName);

        expect(
            wrapper.find('.sw-migration-error-resolution-field__datepicker input').attributes('disabled'),
        ).not.toBeDefined();
        await wrapper.setProps({ disabled: true });
        expect(wrapper.find('.sw-migration-error-resolution-field__datepicker input').attributes('disabled')).toBeDefined();
    });
});
