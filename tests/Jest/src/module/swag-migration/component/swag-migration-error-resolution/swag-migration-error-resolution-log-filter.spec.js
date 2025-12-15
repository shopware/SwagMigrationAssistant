/**
 * @sw-package after-sales
 */
import { mount, flushPromises, DOMWrapper } from '@vue/test-utils';
import SwagMigrationErrorResolutionLogFilter from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-log-filter';
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

    it('should search for filter options when runId got set', async () => {
        const wrapper = await createWrapper({ ...defaultProps, runId: null });
        await flushPromises();

        const body = new DOMWrapper(document.body);

        expect(migrationLoggingRepositoryMock.search).not.toHaveBeenCalled();
        expect(wrapper.vm.filterOptions).toEqual({
            code: [],
            entity: [],
            field: [],
        });

        await wrapper.setProps({ runId: 'test-run-id' });
        await flushPromises();

        expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledWith(
            expect.objectContaining({
                limit: 1,
                page: 1,
                aggregations: expect.arrayContaining([
                    expect.objectContaining({
                        name: 'codeAggregation',
                    }),
                    expect.objectContaining({
                        name: 'entityAggregation',
                    }),
                    expect.objectContaining({
                        name: 'fieldAggregation',
                    }),
                ]),
            }),
        );

        await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
        await flushPromises();

        await body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code input').trigger('click');
        await flushPromises();

        expect(body.findAll('.mt-select-result__result-item-text').map((item) => item.text())).toStrictEqual([
            'swag-migration.index.error-resolution.codes.MIGRATION-001',
            'swag-migration.index.error-resolution.codes.MIGRATION-002',
        ]);

        await body.find('.swag-migration-error-resolution-log-filter__popover-content-form-status input').trigger('click');
        await flushPromises();

        expect(body.findAll('.mt-select-result__result-item-text').map((item) => item.text())).toStrictEqual([
            'swag-migration.index.error-resolution.step.card.filter.status.options.resolved',
            'swag-migration.index.error-resolution.step.card.filter.status.options.unresolved',
        ]);

        await body.find('.swag-migration-error-resolution-log-filter__popover-content-form-entity input').trigger('click');
        await flushPromises();

        expect(body.findAll('.mt-select-result__result-item-text')).toHaveLength(0);

        await body.find('.swag-migration-error-resolution-log-filter__popover-content-form-field input').trigger('click');
        await flushPromises();

        expect(body.findAll('.mt-select-result__result-item-text').map((item) => item.text())).toStrictEqual([
            'languageId',
            'position',
        ]);

        expect(body.find('.swag-migration-error-resolution-log-filter__popover-content').exists()).toBe(true);

        await body.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
        await flushPromises();

        expect(body.find('.swag-migration-error-resolution-log-filter__popover-content').exists()).toBe(false);
    });

    it('should set & emit value changes', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        const body = new DOMWrapper(document.body);

        await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
        await flushPromises();

        await body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code input').trigger('click');
        await flushPromises();

        await body.find('.mt-select-option--0').trigger('click');
        await flushPromises();

        expect(wrapper.emitted('log-filter-change').at(0)).toStrictEqual([
            {
                code: 'MIGRATION-001',
                entity: null,
                field: null,
                status: null,
            },
        ]);

        await body.find('.swag-migration-error-resolution-log-filter__popover-content-form-status input').trigger('click');
        await flushPromises();

        await body.find('.mt-select-option--0').trigger('click');
        await flushPromises();

        expect(wrapper.emitted('log-filter-change').at(1)).toStrictEqual([
            {
                code: 'MIGRATION-001',
                entity: null,
                field: null,
                status: 'resolved',
            },
        ]);

        await body
            .find(
                '.swag-migration-error-resolution-log-filter__popover-content-form-status .mt-select__select-indicator-hitbox',
            )
            .trigger('click');

        expect(wrapper.emitted('log-filter-change').at(2)).toStrictEqual([
            {
                code: 'MIGRATION-001',
                entity: null,
                field: null,
                status: null,
            },
        ]);
    });

    it('should reset selection when clicking reset link', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        const body = new DOMWrapper(document.body);

        await wrapper.find('.swag-migration-error-resolution-log-filter__button').trigger('click');
        await flushPromises();

        await body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code input').trigger('click');
        await flushPromises();

        await body.find('.mt-select-option--0').trigger('click');
        await flushPromises();

        expect(wrapper.emitted('log-filter-change').at(0)).toStrictEqual([
            {
                code: 'MIGRATION-001',
                entity: null,
                field: null,
                status: null,
            },
        ]);

        await body.find('.swag-migration-error-resolution-log-filter__popover-content-reset').trigger('click');

        expect(wrapper.emitted('log-filter-change').at(1)).toStrictEqual([
            {
                code: null,
                entity: null,
                field: null,
                status: null,
            },
        ]);
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
            body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code input').attributes('disabled'),
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
            body.find('.swag-migration-error-resolution-log-filter__popover-content-form-code input').attributes('disabled'),
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
