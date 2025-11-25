/**
 * @sw-package after-sales
 */
import { mount, flushPromises, DOMWrapper } from '@vue/test-utils';
import SwagMigrationErrorResolutionLogFilter, {
    fieldMap,
} from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-log-filter';
import SwagMigrationErrorResolutionService from 'SwagMigrationAssistant/module/swag-migration/service/swag-migration-error-resolution.service';
import { fixtureLogGroups } from '@/fixture';

Shopware.Component.register('swag-migration-error-resolution-log-filter', SwagMigrationErrorResolutionLogFilter);

const defaultProps = {
    disabled: false,
    tableData: fixtureLogGroups,
    runId: 'test-run-id',
};

const defaultSearchResult = {
    aggregations: {
        fieldAggregation: {
            name: 'fieldAggregation',
            buckets: [
                { key: 'languageId' },
                { key: 'position' },
            ],
        },
        entityAggregation: {
            name: 'entityAggregation',
        },
        codeAggregation: {
            name: 'codeAggregation',
            buckets: [
                { key: 'MIGRATION-001' },
                { key: 'MIGRATION-002' },
            ],
        },
    },
};

const migrationLoggingRepositoryMock = {
    search: jest.fn(() => Promise.resolve(defaultSearchResult)),
};

const repositoryFactoryMock = {
    create: (name) => {
        if (name === 'swag_migration_logging') {
            return migrationLoggingRepositoryMock;
        }

        return null;
    },
};

async function createWrapper(props = defaultProps) {
    return mount(await Shopware.Component.build('swag-migration-error-resolution-log-filter'), {
        props,
        attachTo: document.body,
        global: {
            stubs: {},
            provide: {
                repositoryFactory: repositoryFactoryMock,
                swagMigrationErrorResolutionService: new SwagMigrationErrorResolutionService(),
            },
        },
    });
}

describe('src/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-log-filter', () => {
    afterEach(() => {
        jest.clearAllMocks();
        jest.useRealTimers();
        document.body.innerHTML = '';
    });

    describe('constants', () => {
        it('should have a fieldMap constant', () => {
            expect(fieldMap).toStrictEqual({
                code: 'code',
                entity: 'entityName',
                field: 'fieldName',
            });
        });
    });

    describe('popover interaction', () => {
        it('should set initial filter value and render popover content on click', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.vm.value).toStrictEqual({
                code: null,
                entity: null,
                field: null,
                status: null,
            });

            // also get the teleported content
            const body = new DOMWrapper(document.body);

            expect(body.find('.swag-migration-error-resolution-log-filter__popover-content').exists()).toBe(false);
            wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');

            expect(wrapper.vm.loading).toBe(true);
            await flushPromises();
            expect(wrapper.vm.loading).toBe(false);

            expect(body.find('.swag-migration-error-resolution-log-filter__popover-content').exists()).toBe(true);
            expect(body.find('.swag-migration-error-resolution-log-filter__popover-content-reset').exists()).toBe(true);

            expect(
                body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code input').element.value,
            ).toBe('');
            expect(
                body.find('.swag-migration-error-resolution-log-filter__popover-content-form-status input').element.value,
            ).toBe('');
            expect(
                body.find('.swag-migration-error-resolution-log-filter__popover-content-form-entity input').element.value,
            ).toBe('');
            expect(
                body.find('.swag-migration-error-resolution-log-filter__popover-content-form-field input').element.value,
            ).toBe('');

            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(3);

            [
                'codeAggregation',
                'entityAggregation',
                'fieldAggregation',
            ].forEach((aggName, index) => {
                expect(migrationLoggingRepositoryMock.search).toHaveBeenNthCalledWith(
                    index + 1,
                    expect.objectContaining({
                        aggregations: expect.arrayContaining([
                            expect.objectContaining({
                                name: aggName,
                            }),
                        ]),
                    }),
                );
            });

            await body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code input').trigger('click');
            expect(body.find('.mt-select-result-list__item-list').exists()).toBe(true);
            expect(body.findAll('.mt-select-result__result-item-text').map((item) => item.text())).toStrictEqual([
                'swag-migration.index.error-resolution.codes.MIGRATION-001',
                'swag-migration.index.error-resolution.codes.MIGRATION-002',
            ]);

            await body
                .find('.swag-migration-error-resolution-log-filter__popover-content-form-status input')
                .trigger('click');
            expect(body.find('.mt-select-result-list__item-list').exists()).toBe(true);
            expect(body.findAll('.mt-select-result__result-item-text').map((item) => item.text())).toStrictEqual([
                'swag-migration.index.error-resolution.step.card.filter.status.options.resolved',
                'swag-migration.index.error-resolution.step.card.filter.status.options.unresolved',
            ]);

            await body
                .find('.swag-migration-error-resolution-log-filter__popover-content-form-entity input')
                .trigger('click');
            expect(body.find('.mt-select-result-list__item-list').exists()).toBe(true);
            expect(body.findAll('.mt-select-result__result-item-text')).toHaveLength(0);

            await body
                .find('.swag-migration-error-resolution-log-filter__popover-content-form-field input')
                .trigger('click');
            expect(body.find('.mt-select-result-list__item-list').exists()).toBe(true);
            expect(body.findAll('.mt-select-result__result-item-text').map((item) => item.text())).toStrictEqual([
                'languageId',
                'position',
            ]);
        });

        it('should not re-fetch options when closing popover', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            const body = new DOMWrapper(document.body);

            expect(body.find('.swag-migration-error-resolution-log-filter__popover-content').exists()).toBe(false);
            await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            expect(body.find('.swag-migration-error-resolution-log-filter__popover-content').exists()).toBe(true);
            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(3);

            await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            expect(body.find('.swag-migration-error-resolution-log-filter__popover-content').exists()).toBe(false);
            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(3);

            await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            expect(body.find('.swag-migration-error-resolution-log-filter__popover-content').exists()).toBe(true);
            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(6);
        });

        it('should stop propagation if target is inside a select result list', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            const body = new DOMWrapper(document.body);

            await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            await body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code input').trigger('click');
            await flushPromises();

            expect(body.find('.mt-select-result-list__item-list').exists()).toBe(true);

            // Create a mock element inside mt-select-result-list-popover-wrapper
            const mockResultListWrapper = document.createElement('div');
            mockResultListWrapper.classList.add('mt-select-result-list-popover-wrapper');

            const mockTarget = document.createElement('div');
            mockResultListWrapper.appendChild(mockTarget);
            document.body.appendChild(mockResultListWrapper);

            const pointerDownEvent = new MouseEvent('pointerdown', {
                bubbles: true,
                cancelable: true,
            });

            Object.defineProperty(pointerDownEvent, 'target', {
                writable: false,
                value: mockTarget,
            });

            const stopPropagationSpy = jest.spyOn(pointerDownEvent, 'stopPropagation');

            mockTarget.dispatchEvent(pointerDownEvent);
            expect(stopPropagationSpy).toHaveBeenCalled();

            mockResultListWrapper.remove();
        });

        it('should not stop propagation if target is outside a select result list', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            const body = new DOMWrapper(document.body);

            await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            await body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code input').trigger('click');
            await flushPromises();

            const popoverContent = body.find('.swag-migration-error-resolution-log-filter__popover-content').element;
            const pointerDownEvent = new MouseEvent('pointerdown', {
                bubbles: true,
                cancelable: true,
            });

            Object.defineProperty(pointerDownEvent, 'target', {
                writable: false,
                value: popoverContent,
            });

            const stopPropagationSpy = jest.spyOn(pointerDownEvent, 'stopPropagation');

            popoverContent.dispatchEvent(pointerDownEvent);
            expect(stopPropagationSpy).not.toHaveBeenCalled();
        });

        it('should disable filter button when disabled prop is true', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            const body = new DOMWrapper(document.body);

            await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            expect(body.find('.swag-migration-error-resolution-log-filter__button').attributes('disabled')).toBeUndefined();
            expect(
                body
                    .find('.swag-migration-error-resolution-log-filter__popover-content-form-code input')
                    .attributes('disabled'),
            ).toBeUndefined();
            expect(
                body
                    .find('.swag-migration-error-resolution-log-filter__popover-content-form-status input')
                    .attributes('disabled'),
            ).toBeUndefined();
            expect(
                body
                    .find('.swag-migration-error-resolution-log-filter__popover-content-form-entity input')
                    .attributes('disabled'),
            ).toBeUndefined();
            expect(
                body
                    .find('.swag-migration-error-resolution-log-filter__popover-content-form-field input')
                    .attributes('disabled'),
            ).toBeUndefined();
            expect(body.find('.mt-link--disabled').exists()).toBe(false);

            await wrapper.setProps({ disabled: true });
            await flushPromises();

            expect(body.find('.swag-migration-error-resolution-log-filter__button').attributes('disabled')).toBeDefined();
            expect(
                body
                    .find('.swag-migration-error-resolution-log-filter__popover-content-form-code input')
                    .attributes('disabled'),
            ).toBeDefined();
            expect(
                body
                    .find('.swag-migration-error-resolution-log-filter__popover-content-form-status input')
                    .attributes('disabled'),
            ).toBeDefined();
            expect(
                body
                    .find('.swag-migration-error-resolution-log-filter__popover-content-form-entity input')
                    .attributes('disabled'),
            ).toBeDefined();
            expect(
                body
                    .find('.swag-migration-error-resolution-log-filter__popover-content-form-field input')
                    .attributes('disabled'),
            ).toBeDefined();
            expect(body.find('.mt-link--disabled').exists()).toBe(true);
        });
    });

    describe('reset', () => {
        it('should reset filter values on reset button click', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            const body = new DOMWrapper(document.body);

            wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            await body
                .find('.swag-migration-error-resolution-log-filter__popover-content-form-status input')
                .trigger('click');
            await flushPromises();

            await body.findAll('.mt-select-result-list .mt-select-result').at(0).trigger('click');
            await flushPromises();

            expect(wrapper.vm.value).toStrictEqual({
                code: null,
                entity: null,
                field: null,
                status: 'resolved',
            });

            migrationLoggingRepositoryMock.search.mockClear();

            await body.find('.swag-migration-error-resolution-log-filter__popover-content-reset').trigger('click');
            await flushPromises();

            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(3);

            expect(wrapper.vm.value).toStrictEqual({
                code: null,
                entity: null,
                field: null,
                status: null,
            });
        });

        it('should reload single option after clear', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            const body = new DOMWrapper(document.body);

            wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            await body
                .find('.swag-migration-error-resolution-log-filter__popover-content-form-status input')
                .trigger('click');
            await flushPromises();

            await body.findAll('.mt-select-result-list .mt-select-result').at(0).trigger('click');
            await flushPromises();

            migrationLoggingRepositoryMock.search.mockClear();

            await body.find('.mt-select__select-indicator-clear').trigger('click');
            await flushPromises();

            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(1);
        });
    });

    describe('search', () => {
        it('should search filter field on input', async () => {
            jest.useFakeTimers();
            const wrapper = await createWrapper();
            await flushPromises();

            const body = new DOMWrapper(document.body);

            await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            migrationLoggingRepositoryMock.search.mockClear();

            const codeSelect = body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code');
            codeSelect.find('input').element.value = 'MIGRATION';
            await codeSelect.find('input').trigger('input');

            jest.runAllTimers();
            await flushPromises();

            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(1);
            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledWith(
                expect.objectContaining({
                    term: 'MIGRATION',
                }),
            );
        });

        it('should not search of search term is less than 2 characters', async () => {
            jest.useFakeTimers();
            const wrapper = await createWrapper();
            await flushPromises();

            const body = new DOMWrapper(document.body);

            await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            migrationLoggingRepositoryMock.search.mockClear();

            const codeSelect = body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code');
            codeSelect.find('input').element.value = 'MI';
            await codeSelect.find('input').trigger('input');

            jest.runAllTimers();
            await flushPromises();

            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(1);
            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledWith(
                expect.objectContaining({
                    term: null,
                }),
            );
        });

        it('should empty the options if no results are found', async () => {
            jest.useFakeTimers();
            const wrapper = await createWrapper();
            await flushPromises();

            const body = new DOMWrapper(document.body);

            await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
            await flushPromises();

            migrationLoggingRepositoryMock.search.mockClear();
            migrationLoggingRepositoryMock.search.mockReturnValueOnce(
                Promise.resolve({
                    aggregations: {
                        codeAggregation: {
                            name: 'codeAggregation',
                        },
                    },
                }),
            );

            const codeSelect = body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code');
            codeSelect.find('input').element.value = 'MIGRA';
            await codeSelect.find('input').trigger('input');

            jest.runAllTimers();
            await flushPromises();

            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(1);
            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledWith(
                expect.objectContaining({
                    term: 'MIGRA',
                }),
            );

            expect(wrapper.vm.searchResults.code).toHaveLength(0);
        });
    });
});
