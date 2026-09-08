/**
 * @sw-package after-sales
 */
import { DOMWrapper, mount } from '@vue/test-utils';
import SwagMigrationErrorResolutionFieldRelation from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field-relation';
import SwagMigrationErrorResolutionService from 'SwagMigrationAssistant/module/swag-migration/service/swag-migration-error-resolution.service';

Shopware.Component.register(
    'swag-migration-error-resolution-field-relation',
    () => SwagMigrationErrorResolutionFieldRelation,
);

const updateFieldValueMock = jest.fn();

const repositoryMock = {
    search: jest.fn(() =>
        Promise.resolve([
            { id: '1', name: 'result 1' },
            { id: '2', name: 'result 2' },
            { id: '3' }, // empty case
        ]),
    ),
};

const defaultProps = {
    relationType: 'many_to_one',
    entityField: {
        entity: 'tax',
        localField: 'taxId',
        referenceField: 'id',
        relation: 'many_to_one',
        type: 'association',
    },
    fieldName: 'taxId',
    disabled: false,
};

const defaultToManyProps = {
    ...defaultProps,
    relationType: 'many_to_many',
    fieldName: 'categories',
    entityField: {
        entity: 'category',
        local: 'productId',
        localField: 'id',
        mapping: 'product_category',
        reference: 'categoryId',
        referenceField: 'id',
        relation: 'many_to_many',
        type: 'association',
    },
};

async function createWrapper(props = defaultProps) {
    return mount(await Shopware.Component.build('swag-migration-error-resolution-field-relation'), {
        props,
        global: {
            stubs: {
                'sw-entity-multi-id-select': await wrapTestComponent('sw-entity-multi-id-select'),
                'sw-select-selection-list': await wrapTestComponent('sw-select-selection-list'),
                'sw-entity-single-select': await wrapTestComponent('sw-entity-single-select'),
                'sw-entity-multi-select': await wrapTestComponent('sw-entity-multi-select'),
                'sw-select-result-list': await wrapTestComponent('sw-select-result-list'),
                'sw-popover-deprecated': await wrapTestComponent('sw-popover-deprecated'),
                'sw-select-result': await wrapTestComponent('sw-select-result'),
                'sw-select-base': await wrapTestComponent('sw-select-base'),
                'sw-block-field': await wrapTestComponent('sw-block-field'),
                'sw-base-field': await wrapTestComponent('sw-base-field'),
                'sw-popover': await wrapTestComponent('sw-popover'),
                'sw-product-variant-info': true,
                'sw-inheritance-switch': true,
                'sw-ai-copilot-badge': true,
                'sw-highlight-text': true,
                'sw-field-error': true,
                'sw-help-text': true,
                'sw-loader': true,
                'sw-label': true,
            },
            provide: {
                updateFieldValue: updateFieldValueMock,
                swagMigrationErrorResolutionService: new SwagMigrationErrorResolutionService(),
                repositoryFactory: {
                    create: () => repositoryMock,
                },
            },
        },
    });
}

describe('src/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field-relation', () => {
    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('to-one relation', () => {
        it('should display to-one select if relation is to-one', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.find('.sw-migration-error-resolution-field__to-one').exists()).toBe(true);

            // should set correct initial value
            expect(wrapper.vm.fieldValue).toBeNull();

            expect(wrapper.find('.swag-migration-error-resolution-field-relation__info-banner').exists()).toBe(false);
            expect(repositoryMock.search).toHaveBeenNthCalledWith(
                1,
                expect.objectContaining({
                    page: 1,
                    limit: 1,
                    includes: {
                        tax: ['id'],
                    },
                }),
            );
        });

        it('should not display to-one select if entity is undefined', async () => {
            const wrapper = await createWrapper({
                ...defaultProps,
                entityField: {
                    ...defaultProps.entityField,
                    entity: undefined,
                },
            });
            await flushPromises();

            expect(wrapper.find('.sw-migration-error-resolution-field__to-one').exists()).toBe(false);
        });

        it('should display label with meaningful value', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            await wrapper.find('.sw-migration-error-resolution-field__to-one input').trigger('click');
            await flushPromises();

            expect(new DOMWrapper(document.body).find('.sw-select-result-list__content').exists()).toBe(true);

            const labels = new DOMWrapper(document.body).findAll(
                '.swag-migration-error-resolution-field-relation__item-label',
            );
            const ids = new DOMWrapper(document.body).findAll('.swag-migration-error-resolution-field-relation__item-id');

            expect(labels[0].text()).toBe('result 1');
            expect(ids[0].text()).toBe('1');
            expect(labels[1].text()).toBe('result 2');
            expect(ids[1].text()).toBe('2');
            expect(labels[2].text()).toBe('');
            expect(ids[2].text()).toBe('3');
        });

        it('should display error banner & link if no results are found', async () => {
            jest.spyOn(Shopware.Module, 'getModuleByEntityName').mockReturnValueOnce(null);

            repositoryMock.search.mockImplementationOnce(() =>
                Promise.resolve({
                    total: 0,
                }),
            );

            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.find('.swag-migration-error-resolution-field-relation__info-banner').exists()).toBe(true);
            expect(wrapper.find('.swag-migration-error-resolution-field-relation__info-banner-link').exists()).toBe(false);
        });
    });

    describe('to-many relation', () => {
        it.each([
            { type: 'many_to_many' },
            { type: 'one_to_many' },
        ])('should display to-many select if relation is to-many: $type', async ({ type }) => {
            const wrapper = await createWrapper({
                ...defaultToManyProps,
                relationType: type,
            });
            await flushPromises();

            expect(wrapper.find('.sw-migration-error-resolution-field__to_many').exists()).toBe(true);

            // should set correct initial value
            expect(wrapper.vm.fieldValue).toStrictEqual([]);

            expect(wrapper.find('.swag-migration-error-resolution-field-relation__info-banner').exists()).toBe(false);
            expect(repositoryMock.search).toHaveBeenNthCalledWith(
                1,
                expect.objectContaining({
                    page: 1,
                    limit: 1,
                    includes: {
                        category: ['id'],
                    },
                }),
            );
        });

        it('should not display to-many select if entity is undefined', async () => {
            const wrapper = await createWrapper({
                ...defaultToManyProps,
                entityField: {
                    ...defaultToManyProps.entityField,
                    entity: undefined,
                },
            });
            await flushPromises();

            expect(wrapper.find('.sw-migration-error-resolution-field__to_many').exists()).toBe(false);
        });

        it('should display label with meaningful value', async () => {
            const wrapper = await createWrapper(defaultToManyProps);
            await flushPromises();

            await wrapper.find('.sw-migration-error-resolution-field__to_many input').trigger('click');
            await flushPromises();

            expect(new DOMWrapper(document.body).find('.sw-select-result-list__content').exists()).toBe(true);

            const labels = new DOMWrapper(document.body).findAll(
                '.swag-migration-error-resolution-field-relation__item-label',
            );
            const ids = new DOMWrapper(document.body).findAll('.swag-migration-error-resolution-field-relation__item-id');

            expect(labels[0].text()).toBe('result 1');
            expect(ids[0].text()).toBe('1');
            expect(labels[1].text()).toBe('result 2');
            expect(ids[1].text()).toBe('2');
            expect(labels[2].text()).toBe('');
            expect(ids[2].text()).toBe('3');
        });

        it('should display error banner & link if no results are found', async () => {
            jest.spyOn(Shopware.Module, 'getModuleByEntityName').mockReturnValueOnce({
                routes: new Map(
                    Object.entries({
                        index: {
                            routeKey: 'index',
                            name: 'index',
                        },
                    }),
                ),
            });

            repositoryMock.search.mockImplementationOnce(() =>
                Promise.resolve({
                    total: 0,
                }),
            );

            const wrapper = await createWrapper({
                ...defaultToManyProps,
                entityField: {
                    ...defaultToManyProps.entityField,
                    entity: 'product',
                },
            });
            await flushPromises();

            expect(wrapper.find('.swag-migration-error-resolution-field-relation__info-banner').exists()).toBe(true);
            expect(wrapper.find('.swag-migration-error-resolution-field-relation__info-banner-link').exists()).toBe(true);
        });
    });
});
