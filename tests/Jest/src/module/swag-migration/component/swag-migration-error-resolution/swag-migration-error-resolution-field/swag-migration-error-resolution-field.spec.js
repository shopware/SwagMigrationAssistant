/**
 * @sw-package after-sales
 */
import { mount } from '@vue/test-utils';
import SwagMigrationErrorResolutionField from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field';
import SwagMigrationErrorResolutionService from 'SwagMigrationAssistant/module/swag-migration/service/swag-migration-error-resolution.service';

Shopware.Component.register('swag-migration-error-resolution-field', () => SwagMigrationErrorResolutionField);

const migrationApiServiceMock = {
    getExampleFieldStructure: jest.fn().mockResolvedValue(
        Promise.resolve({
            example: 'Example Value',
        }),
    ),
};

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
                migrationApiService: migrationApiServiceMock,
            },
        },
    });
}

describe('src/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field', () => {
    beforeEach(() => {
        Shopware.Store.get('notification').$reset();
    });

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
        await flushPromises();

        expect(migrationApiServiceMock.getExampleFieldStructure).toHaveBeenCalled();

        const field = wrapper.find('swag-migration-error-resolution-field-unhandled-stub');

        expect(field.exists()).toBe(true);
        expect(field.attributes('disabled')).toBe(String(props.disabled));
        expect(field.attributes('field-name')).toBe(props.log.fieldName);
        expect(field.attributes('example-value')).toBe('Example Value');
    });

    it('should display scalar component & pass data', async () => {
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
        await flushPromises();

        expect(migrationApiServiceMock.getExampleFieldStructure).not.toHaveBeenCalled();

        const field = wrapper.find('swag-migration-error-resolution-field-scalar-stub');

        expect(field.exists()).toBe(true);
        expect(field.attributes('disabled')).toBe(String(props.disabled));
        expect(field.attributes('field-name')).toBe(props.log.fieldName);
        expect(field.attributes('component-type')).toBe('text');
    });

    it('should display relation component & pass data', async () => {
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
        await flushPromises();

        expect(migrationApiServiceMock.getExampleFieldStructure).not.toHaveBeenCalled();

        const field = wrapper.find('swag-migration-error-resolution-field-relation-stub');

        expect(field.exists()).toBe(true);
        expect(field.attributes('disabled')).toBe(String(props.disabled));
        expect(field.attributes('field-name')).toBe(props.log.fieldName);
    });

    it('should fetch example field value', async () => {
        const wrapper = await createWrapper({
            ...defaultProps,
            log: {
                ...defaultProps.log,
                entityName: 'product',
                fieldName: 'price',
            },
        });
        await flushPromises();

        expect(migrationApiServiceMock.getExampleFieldStructure).toHaveBeenCalled();

        expect(wrapper.find('swag-migration-error-resolution-field-scalar-stub').exists()).toBe(true);
        expect(wrapper.find('swag-migration-error-resolution-field-scalar-stub').attributes('example-value')).toBe(
            'Example Value',
        );
    });

    it('should display error notification on fetch failure', async () => {
        migrationApiServiceMock.getExampleFieldStructure.mockRejectedValueOnce(new Error('Fetch failed'));

        await createWrapper({
            ...defaultProps,
            log: {
                ...defaultProps.log,
                entityName: 'product',
                fieldName: 'price',
            },
        });
        await flushPromises();

        expect(migrationApiServiceMock.getExampleFieldStructure).toHaveBeenCalled();

        const notifications = Object.values(Shopware.Store.get('notification').notifications);

        expect(notifications).toHaveLength(1);
        expect(notifications).toStrictEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    message: 'swag-migration.index.error-resolution.errors.fetchExampleFailed',
                }),
            ]),
        );
    });
});
