/**
 * @sw-package after-sales
 */
import { mount } from '@vue/test-utils';
import SwagMigrationErrorResolutionField from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field';
import SwagMigrationErrorResolutionService from 'SwagMigrationAssistant/module/swag-migration/service/swag-migration-error-resolution.service';

Shopware.Component.register('swag-migration-error-resolution-field', () => SwagMigrationErrorResolutionField);

const defaultProps = {
    log: {
        count: 10,
        fixedCount: 2,
        code: 'SWAG_MIGRATION_ERROR_CODE',
        entityName: 'customer',
        fieldName: 'name',
        resolved: false,
    },
    disabled: false,
};

async function createWrapper(props = defaultProps) {
    return mount(await Shopware.Component.build('swag-migration-error-resolution-field'), {
        props,
        global: {
            stubs: {
                'swag-migration-error-resolution-field-unhandled': true,
                'swag-migration-error-resolution-field-scalar': true,
                'swag-migration-error-resolution-field-relation': true,
            },
            provide: {
                swagMigrationErrorResolutionService: new SwagMigrationErrorResolutionService(),
            },
        },
    });
}

describe('src/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field', () => {
    it('should display unhandled component & pass data', async () => {
        const props = {
            ...defaultProps,
            log: {
                ...defaultProps.log,
                entityName: 'customer',
                fieldName: 'password',
            },
            disabled: true,
        };

        const wrapper = await createWrapper(props);

        const field = wrapper.find('swag-migration-error-resolution-field-unhandled-stub');

        expect(field.exists()).toBe(true);
        expect(field.attributes('disabled')).toBe(String(props.disabled));
        expect(field.attributes('field-name')).toBe(props.log.fieldName);
    });

    it('should display scalar component & pass data ', async () => {
        const props = {
            ...defaultProps,
            log: {
                ...defaultProps.log,
                entityName: 'customer',
                fieldName: 'firstName',
            },
            disabled: true,
        };

        const wrapper = await createWrapper(props);

        const field = wrapper.find('swag-migration-error-resolution-field-scalar-stub');

        expect(field.exists()).toBe(true);
        expect(field.attributes('disabled')).toBe(String(props.disabled));
        expect(field.attributes('field-name')).toBe(props.log.fieldName);
        expect(field.attributes('component-type')).toBe('text');
    });

    it('should display relation component & pass data ', async () => {
        const props = {
            ...defaultProps,
            log: {
                ...defaultProps.log,
                entityName: 'customer',
                fieldName: 'salutationId',
            },
            disabled: true,
        };

        const wrapper = await createWrapper(props);

        const field = wrapper.find('swag-migration-error-resolution-field-relation-stub');

        expect(field.exists()).toBe(true);
        expect(field.attributes('disabled')).toBe(String(props.disabled));
        expect(field.attributes('field-name')).toBe(props.log.fieldName);
    });
});
