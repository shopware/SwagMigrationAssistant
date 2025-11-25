/**
 * @sw-package after-sales
 */
import { mount } from '@vue/test-utils';
import SwagMigrationErrorResolutionStep, {
    MIGRATION_LOG_LEVEL,
} from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-step';
import SwagMigrationErrorResolutionService from 'SwagMigrationAssistant/module/swag-migration/service/swag-migration-error-resolution.service';
import { fixtureLogGroups } from '@/fixture';

Shopware.Component.register('swag-migration-error-resolution-step', () => SwagMigrationErrorResolutionStep);

const logGroupResponseMock = {
    items: fixtureLogGroups,
    total: fixtureLogGroups.length,
    levelCounts: {
        info: 0,
        warning: 1,
        error: fixtureLogGroups.length,
    },
};

const migrationApiServiceMock = {
    getLogGroups: jest.fn(() => Promise.resolve(logGroupResponseMock)),
    continueAfterErrorResolution: jest.fn(() => Promise.resolve()),
    downloadLogsOfRun: jest.fn(() => Promise.resolve(new Blob(['log content'], { type: 'text/plain' }))),
};

const migrationLoggingRepositoryMock = {
    search: jest.fn(() => Promise.resolve({
        total: 5,
    })),
};

const migrationRunRepositoryMock = {
    search: jest.fn(() => Promise.resolve({
        first: () => ({ id: 'run-id-1' }),
    })),
};

const repositoryFactoryMock = {
    create: (name) => {
        if (name === 'swag_migration_logging') {
            return migrationLoggingRepositoryMock;
        }

        if (name === 'swag_migration_run') {
            return migrationRunRepositoryMock;
        }

        return null;
    },
};

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-migration-error-resolution-step'), {
        global: {
            stubs: {
                'sw-popover-deprecated': await wrapTestComponent('sw-popover-deprecated'),
                'sw-context-menu-item': await wrapTestComponent('sw-context-menu-item'),
                'sw-context-button': await wrapTestComponent('sw-context-button'),
                'sw-context-menu': await wrapTestComponent('sw-context-menu'),
                'sw-data-grid': await wrapTestComponent('sw-data-grid'),
                'sw-provide': await wrapTestComponent('sw-provide'),
                'sw-popover': await wrapTestComponent('sw-popover'),
                'sw-pagination': await wrapTestComponent('sw-pagination'),
                'swag-migration-error-resolution-log-filter': true,
                'swag-migration-error-resolution-modal': true,
                'sw-data-grid-column-boolean': true,
                'sw-data-grid-inline-edit': true,
                'sw-data-grid-skeleton': true,
                'sw-data-grid-settings': true,
                'router-link': true,
            },
            provide: {
                repositoryFactory: repositoryFactoryMock,
                migrationApiService: migrationApiServiceMock,
                swagMigrationErrorResolutionService: new SwagMigrationErrorResolutionService(),
            },
        },
    });
}

describe('src/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-step', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        Shopware.Store.get('notification').$reset();
    });

    describe('constants', () => {
        it('should provide migration error log level constants', () => {
            expect(MIGRATION_LOG_LEVEL).toStrictEqual({
                INFO: 'info',
                WARNING: 'warning',
                ERROR: 'error',
            });
        });
    });

    describe('initial creation', () => {
        it('should fetch run, logs and totals on initial creation', async () => {
            const wrapper = await createWrapper();

            // should disable ui during initial load
            expect(wrapper.vm.loading).toBe(true);
            expect(
                wrapper.find('.swag-migration-error-resolution-step__header-buttons-download').attributes('disabled'),
            ).toBeDefined();
            expect(
                wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue').attributes('disabled'),
            ).toBeDefined();
            expect(wrapper.find('swag-migration-error-resolution-log-filter-stub').attributes('disabled')).toBe('true');
            expect(wrapper.find('.swag-migration-error-resolution-step__card-table-grid').attributes('is-loading')).toBe(
                'true',
            );
            await flushPromises();

            expect(wrapper.vm.loading).toBe(false);

            // should keep default tabItem
            expect(wrapper.vm.tabItem).toBe(MIGRATION_LOG_LEVEL.ERROR);

            // should fetch run with correct criteria
            expect(migrationRunRepositoryMock.search).toHaveBeenNthCalledWith(
                1,
                expect.objectContaining({
                    page: 1,
                    limit: 1,
                    filters: [
                        { type: 'equals', field: 'connectionId', value: null },
                        { type: 'equals', field: 'step', value: 'apply-fixes' },
                    ],
                    includes: { swag_migration_run: ['id'] },
                }),
            );

            const initialLogParams = [
                'run-id-1',
                MIGRATION_LOG_LEVEL.ERROR,
                1,
                25,
                'count',
                'DESC',
                expect.any(Object),
            ];

            // should fetch logs with correct params
            expect(migrationApiServiceMock.getLogGroups).toHaveBeenNthCalledWith(1, ...initialLogParams);

            // should map fetched data
            expect(wrapper.vm.tableTotal).toBe(logGroupResponseMock.total);
            expect(wrapper.vm.levelCounts).toStrictEqual(logGroupResponseMock.levelCounts);
            expect(wrapper.vm.tableData).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        name: 'swag-migration.index.error-resolution.codes.SWAG_MIGRATION_VALIDATION_MISSING_REQUIRED_FIELD',
                        resolved: true,
                    }),
                    expect.objectContaining({
                        name: 'swag-migration.index.error-resolution.codes.SWAG_MIGRATION_VALIDATION_INVALID_FIELD_VALUE',
                        resolved: false,
                    }),
                ]),
            );
            expect(wrapper.findAll('.sw-data-grid__row')).toHaveLength(
                logGroupResponseMock.items.length + 1, // +1 for the header row
            );

            // should fetch total unfixable errors
            expect(migrationLoggingRepositoryMock.search).toHaveBeenNthCalledWith(
                1,
                expect.objectContaining({
                    page: 1,
                    limit: 1,
                    filters: [
                        expect.objectContaining({ type: 'equals', field: 'userFixable', value: false }),
                        expect.objectContaining({ type: 'equals', field: 'runId', value: 'run-id-1' }),
                    ],
                }),
            );
            expect(wrapper.vm.totalUnfixableErrors).toBe(5);
            expect(wrapper.find('.swag-migration-error-resolution-step__header-banner').exists()).toBe(true);
        });

        it('should display error notification on fetch run failure', async () => {
            migrationRunRepositoryMock.search.mockReturnValueOnce(Promise.reject(new Error('Fetch run failed')));

            await createWrapper();
            await flushPromises();

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.fetchRunFailed',
                    }),
                ]),
            );
        });

        it('should escape loading when run is not found', async () => {
            migrationRunRepositoryMock.search.mockReturnValueOnce(
                Promise.resolve({
                    first: () => null,
                }),
            );

            const wrapper = await createWrapper();
            await flushPromises();

            expect(migrationLoggingRepositoryMock.search).not.toHaveBeenCalled();
            expect(migrationApiServiceMock.getLogGroups).not.toHaveBeenCalled();
            expect(wrapper.vm.loading).toBe(false);

            expect(Object.values(Shopware.Store.get('notification').notifications)).toHaveLength(0);
        });

        it.each([
            {
                name: 'fixable & unfixable errors',
                logTotal: 1,
                unfixableTotal: 1,
                expected: 0,
            },
            {
                name: 'no fixable & unfixable errors',
                logTotal: 0,
                unfixableTotal: 1,
                expected: 0,
            },
            {
                name: 'fixable & no unfixable errors',
                logTotal: 1,
                unfixableTotal: 0,
                expected: 0,
            },
            {
                name: 'no errors',
                logTotal: 1,
                unfixableTotal: 0,
                expected: 0,
            },
        ])(
            'should directly continue migration if no errors where logged: $name',
            async ({ logTotal, unfixableTotal, expected }) => {
                migrationApiServiceMock.getLogGroups.mockReturnValueOnce(
                    Promise.resolve({
                        items: [],
                        total: logTotal,
                        levelCounts: {
                            info: 0,
                            warning: 0,
                            error: logTotal,
                        },
                    }),
                );

                migrationLoggingRepositoryMock.search.mockReturnValueOnce(
                    Promise.resolve({
                        total: unfixableTotal,
                    }),
                );

                await createWrapper();
                await flushPromises();

                expect(migrationApiServiceMock.continueAfterErrorResolution).toHaveBeenCalledTimes(expected);
            },
        );
    });

    describe('data table interaction', () => {
        it('should display empty state when no logs are found', async () => {
            migrationApiServiceMock.getLogGroups.mockReturnValueOnce(
                Promise.resolve({
                    items: [],
                    total: 0,
                    levelCounts: {
                        info: 0,
                        warning: 0,
                        error: 0,
                    },
                }),
            );

            const wrapper = await createWrapper();

            // should not display empty state during loading state
            expect(wrapper.vm.loading).toBe(true);
            expect(wrapper.find('.swag-migration-error-resolution-step__card-table-grid').exists()).toBe(true);
            expect(wrapper.find('.swag-migration-error-resolution-step__card-table-empty-state').exists()).toBe(false);
            expect(wrapper.find('.swag-migration-error-resolution-step__card-table-grid').attributes('is-loading')).toBe(
                'true',
            );

            await flushPromises();

            expect(wrapper.vm.loading).toBe(false);
            expect(wrapper.find('.swag-migration-error-resolution-step__card-table-grid').exists()).toBe(false);
            expect(wrapper.find('.swag-migration-error-resolution-step__card-table-empty-state').exists()).toBe(true);
        });

        it('should display data table columns correctly', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            // should be sortable
            expect(wrapper.findAll('.sw-data-grid__cell--sortable')).toHaveLength(7);

            const columnNames = wrapper
                .findAll('.sw-data-grid__header th .sw-data-grid__cell-content')
                .map((header) => header.text())
                .filter((t) => !!t);

            // correct order
            expect(columnNames).toStrictEqual([
                'swag-migration.index.error-resolution.step.card.table.columns.count',
                'swag-migration.index.error-resolution.step.card.table.columns.name',
                'swag-migration.index.error-resolution.step.card.table.columns.entity',
                'swag-migration.index.error-resolution.step.card.table.columns.field',
                'swag-migration.index.error-resolution.step.card.table.columns.code',
                'swag-migration.index.error-resolution.step.card.table.columns.profileName',
                'swag-migration.index.error-resolution.step.card.table.columns.gatewayName',
            ]);

            // correct status content
            const statusCellsContent = wrapper
                .findAll('.swag-migration-error-resolution-step__card-table-count-text')
                .map((cell) => cell.text());
            expect(statusCellsContent).toStrictEqual([
                '591 / 591',
                '13 / 161',
            ]);

            // correct status icons
            expect(wrapper.findAll('.swag-migration-error-resolution-step__card-table-count-icon')).toHaveLength(1);
            expect(
                wrapper.find('.sw-data-grid__row--0 .swag-migration-error-resolution-step__card-table-count-icon').exists(),
            ).toBe(true);
        });

        describe('resolution modal', () => {
            it('should open resolution modal via row click', async () => {
                const wrapper = await createWrapper();
                await flushPromises();

                expect(wrapper.find('.sw-data-grid__row--0').exists()).toBe(true);
                await wrapper.find('.swag-migration-error-resolution-step__card-table-error-link').trigger('click');
                await flushPromises();

                const modal = wrapper.find('swag-migration-error-resolution-modal-stub');

                expect(wrapper.vm.openErrorResolutionModal).toBe(true);
                expect(wrapper.vm.selectedLog).toStrictEqual(
                    expect.objectContaining({
                        ...fixtureLogGroups.at(0),
                    }),
                );

                expect(modal.exists()).toBe(true);
                expect(modal.attributes('selected-log')).toBeDefined();
                expect(modal.attributes('run-id')).toBe('run-id-1');
            });

            it('should open resolution modal via content menu', async () => {
                const wrapper = await createWrapper();
                await flushPromises();

                expect(wrapper.find('.sw-data-grid__row--0').exists()).toBe(true);
                await wrapper.find('.sw-data-grid__row--0 .sw-data-grid__cell--actions button').trigger('click');
                await flushPromises();

                expect(wrapper.find('.sw-context-button__menu-popover').exists()).toBe(true);
                await wrapper.find('.swag-migration-error-resolution-step__card-table-edit').trigger('click');
                await flushPromises();

                const modal = wrapper.find('swag-migration-error-resolution-modal-stub');

                expect(wrapper.vm.openErrorResolutionModal).toBe(true);
                expect(wrapper.vm.selectedLog).toStrictEqual(
                    expect.objectContaining({
                        ...fixtureLogGroups.at(0),
                    }),
                );

                expect(modal.exists()).toBe(true);
                expect(modal.attributes('selected-log')).toBeDefined();
                expect(modal.attributes('run-id')).toBe('run-id-1');

                // should also close the modal on emit
                await modal.trigger('close-error-resolution-modal');
                await flushPromises();

                expect(wrapper.vm.openErrorResolutionModal).toBe(false);
                expect(wrapper.vm.selectedLog).toBeNull();
                expect(wrapper.find('swag-migration-error-resolution-modal-stub').exists()).toBe(false);
            });

            it('should trigger refetch of logs if fixes were applied in resolution modal', async () => {
                const wrapper = await createWrapper();
                await flushPromises();

                expect(wrapper.find('.sw-data-grid__row--0').exists()).toBe(true);
                await wrapper.find('.sw-data-grid__row--0 .sw-data-grid__cell--actions button').trigger('click');
                await flushPromises();

                expect(wrapper.find('.sw-context-button__menu-popover').exists()).toBe(true);
                await wrapper.find('.swag-migration-error-resolution-step__card-table-edit').trigger('click');
                await flushPromises();

                expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(1);
                migrationApiServiceMock.getLogGroups.mockClear();

                expect(wrapper.find('swag-migration-error-resolution-modal-stub').exists()).toBe(true);
                await wrapper.find('swag-migration-error-resolution-modal-stub').trigger('fixes-created');
                await flushPromises();

                expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(1);
            });
        });

        describe('filter, sort & paginate logs', () => {
            it('should filter logs by level (tab change)', async () => {
                const wrapper = await createWrapper();
                await wrapper.setData({ tablePage: 2 });
                await flushPromises();

                const tabButtons = wrapper.findAll('.swag-migration-error-resolution-step__card .mt-tabs__item');
                expect(tabButtons).toHaveLength(3);

                const tabNames = tabButtons.map((btn) => btn.text());
                expect(tabNames).toStrictEqual([
                    'swag-migration.index.error-resolution.step.card.tabs.errors',
                    'swag-migration.index.error-resolution.step.card.tabs.warnings',
                    'swag-migration.index.error-resolution.step.card.tabs.infos',
                ]);

                expect(wrapper.findAll('.mt-tabs__item--active')).toHaveLength(1);
                expect(wrapper.find('.mt-tabs__item--active').attributes('data-item-name')).toBe(MIGRATION_LOG_LEVEL.ERROR);

                expect(wrapper.find('.mt-tabs__item[data-item-name="error"]').attributes('disabled')).toBeUndefined();
                expect(wrapper.find('.mt-tabs__item[data-item-name="warning"]').attributes('disabled')).toBeUndefined();
                expect(wrapper.find('.mt-tabs__item[data-item-name="info"]').attributes('disabled')).toBeDefined();

                migrationApiServiceMock.getLogGroups.mockClear();

                // trigger the same should trigger reload
                await wrapper.find('.mt-tabs__item[data-item-name="error"]').trigger('click');
                await flushPromises();
                expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(0);

                await wrapper.find('.mt-tabs__item[data-item-name="warning"]').trigger('click');
                await flushPromises();

                expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(1);
                expect(wrapper.vm.tablePage).toBe(1);

                expect(wrapper.find('.mt-tabs__item--active').attributes('data-item-name')).toBe(
                    MIGRATION_LOG_LEVEL.WARNING,
                );
            });

            it('should sort logs by column', async () => {
                const wrapper = await createWrapper();
                await flushPromises();

                expect(wrapper.vm.tableSortBy).toBe('count');
                expect(wrapper.vm.tableSortDirection).toBe('DESC');
                expect(wrapper.find('.sw-data-grid__cell--0 .icon--regular-chevron-down-xxs').exists()).toBe(true);

                await wrapper.find('.sw-data-grid__cell--1').trigger('click');
                expect(wrapper.vm.tableSortBy).toBe('name');
                expect(wrapper.vm.tableSortDirection).toBe('DESC');
                expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(2);
                expect(wrapper.find('.sw-data-grid__cell--1 .icon--regular-chevron-down-xxs').exists()).toBe(true);

                await wrapper.find('.sw-data-grid__cell--1').trigger('click');
                expect(wrapper.vm.tableSortBy).toBe('name');
                expect(wrapper.vm.tableSortDirection).toBe('ASC');
                expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(3);
                expect(wrapper.find('.sw-data-grid__cell--1 .icon--regular-chevron-up-xxs').exists()).toBe(true);
            });

            it('should apply filters and fetch data', async () => {
                const wrapper = await createWrapper();
                await wrapper.setData({ tablePage: 2 });
                await flushPromises();

                const filters = {
                    code: 'SWAG_MIGRATION_VALIDATION_MISSING_REQUIRED_FIELD',
                    status: 'resolved',
                    entity: 'customer',
                    field: 'email',
                };

                const filter = wrapper.find('swag-migration-error-resolution-log-filter-stub');

                expect(filter.exists()).toBe(true);
                await filter.trigger('log-filter-change', filters);
                await flushPromises();

                expect(wrapper.vm.tablePage).toBe(1);

                const params = [
                    'run-id-1',
                    MIGRATION_LOG_LEVEL.ERROR,
                    1,
                    25,
                    'count',
                    'DESC',
                    filters,
                ];

                expect(migrationApiServiceMock.getLogGroups).toHaveBeenNthCalledWith(2, ...params);
            });

            it('should paginate logs', async () => {
                migrationApiServiceMock.getLogGroups.mockReturnValue(
                    Promise.resolve({
                        items: Array.from({ length: 25 }, () => fixtureLogGroups[0]),
                        total: 50,
                        levelCounts: {
                            info: 1,
                            warning: 24,
                            error: 25,
                        },
                    }),
                );

                const wrapper = await createWrapper();
                await flushPromises();

                expect(wrapper.vm.tableTotal).toBe(50);

                expect(wrapper.find('.sw-pagination').exists()).toBe(true);
                expect(wrapper.findAll('.sw-data-grid__row')).toHaveLength(26); // +1 for header row

                expect(wrapper.find('.mt-select-selection-list__input').element.value).toBe('25');
                expect(wrapper.find('.sw-pagination__list-item .is-active').text()).toBe('1');

                // change page
                await wrapper.find('.sw-pagination__page-button-next').trigger('click');
                await flushPromises();

                expect(wrapper.findAll('.sw-data-grid__row')).toHaveLength(26);
                expect(wrapper.find('.sw-pagination__list-item .is-active').text()).toBe('2');
                expect(migrationApiServiceMock.getLogGroups).toHaveBeenNthCalledWith(
                    2,
                    'run-id-1',
                    MIGRATION_LOG_LEVEL.ERROR,
                    2,
                    25,
                    'count',
                    'DESC',
                    expect.any(Object),
                );

                // change limit
                migrationApiServiceMock.getLogGroups.mockReturnValue(
                    Promise.resolve({
                        items: Array.from({ length: 10 }, () => fixtureLogGroups[0]),
                        total: 50,
                        levelCounts: {
                            info: 1,
                            warning: 24,
                            error: 25,
                        },
                    }),
                );

                await wrapper.find('.mt-select__selection').trigger('click');
                await flushPromises();

                expect(wrapper.find('.mt-select-result-list__item-list').exists()).toBe(true);
                await wrapper.find('.mt-select-option--10').trigger('click');
                await flushPromises();

                expect(wrapper.find('.sw-pagination__list-item .is-active').text()).toBe('1');
                expect(wrapper.find('.mt-select-selection-list__input').element.value).toBe('10');
                expect(wrapper.findAll('.sw-data-grid__row')).toHaveLength(11);
                expect(migrationApiServiceMock.getLogGroups).toHaveBeenNthCalledWith(
                    3,
                    'run-id-1',
                    MIGRATION_LOG_LEVEL.ERROR,
                    1,
                    10,
                    'count',
                    'DESC',
                    expect.any(Object),
                );
            });
        });
    });

    describe('download log file', () => {
        it('should download log file & display loading state', async () => {
            window.URL.createObjectURL = jest.fn(() => 'blob:mock-url');
            const appendChildSpy = jest.spyOn(document.body, 'appendChild');
            const removeChildSpy = jest.spyOn(document.body, 'removeChild');
            const clickSpy = jest.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementationOnce(() => {});

            let resolveDownload;

            migrationApiServiceMock.downloadLogsOfRun.mockImplementationOnce(() => {
                return new Promise((resolve) => {
                    resolveDownload = resolve;
                });
            });

            const wrapper = await createWrapper();
            await flushPromises();

            const downloadButton = wrapper.find('.swag-migration-error-resolution-step__header-buttons-download');
            const continueButton = wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue');

            expect(downloadButton.attributes('disabled')).toBeUndefined();
            expect(continueButton.attributes('disabled')).toBeUndefined();

            await downloadButton.trigger('click');

            expect(wrapper.vm.downloadLoading).toBe(true);
            expect(downloadButton.attributes('disabled')).toBeDefined();
            expect(continueButton.attributes('disabled')).toBeDefined();

            resolveDownload(new Blob(['log content'], { type: 'text/plain' }));
            await flushPromises();

            expect(migrationApiServiceMock.downloadLogsOfRun).toHaveBeenCalledWith('run-id-1');

            expect(wrapper.vm.downloadLoading).toBe(false);
            expect(downloadButton.attributes('disabled')).toBeUndefined();
            expect(continueButton.attributes('disabled')).toBeUndefined();

            const linkElement = appendChildSpy.mock.calls.find((call) => call[0] instanceof HTMLAnchorElement)[0];
            expect(linkElement.href).toBe('blob:mock-url');
            expect(linkElement.download).toBe('migration-logs-run-id-1.txt');
            expect(clickSpy).toHaveBeenCalled();
            expect(removeChildSpy).toHaveBeenCalledWith(linkElement);

            appendChildSpy.mockRestore();
            removeChildSpy.mockRestore();
            clickSpy.mockRestore();
        });

        it('should not download log file if migration run was not found', async () => {
            migrationRunRepositoryMock.search.mockReturnValueOnce(
                Promise.resolve({
                    first: () => null,
                }),
            );

            const wrapper = await createWrapper();
            await flushPromises();

            await wrapper.find('.swag-migration-error-resolution-step__header-buttons-download').trigger('click');
            await flushPromises();

            expect(migrationApiServiceMock.downloadLogsOfRun).not.toHaveBeenCalled();
        });

        it('should display error notification on download failure', async () => {
            migrationApiServiceMock.downloadLogsOfRun.mockImplementationOnce(() => Promise.reject(new Error('Download failed')));

            const wrapper = await createWrapper();
            await flushPromises();

            await wrapper.find('.swag-migration-error-resolution-step__header-buttons-download').trigger('click');
            await flushPromises();

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.downloadLogsFailed',
                    }),
                ]),
            );
        });
    });

    describe('continue migration', () => {
        it('should directly continue migration when continuing migration with no open errors', async () => {
            migrationApiServiceMock.getLogGroups.mockReturnValue(
                Promise.resolve({
                    levelCounts: {
                        error: 0,
                    },
                }),
            );

            migrationLoggingRepositoryMock.search.mockReturnValue(
                Promise.resolve({
                    total: 0,
                }),
            );

            const wrapper = await createWrapper();
            await flushPromises();

            expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(1);
            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(1);

            await wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue').trigger('click');

            expect(
                wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue').attributes('disabled'),
            ).toBeDefined();
            expect(wrapper.vm.continueLoading).toBe(true);
            await flushPromises();

            expect(
                wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue').attributes('disabled'),
            ).toBeUndefined();
            expect(wrapper.vm.continueLoading).toBe(false);

            expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(2);
            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(2);
            expect(migrationApiServiceMock.continueAfterErrorResolution).toHaveBeenCalledTimes(1);
        });

        it('should continue migration via the warn modal when only unfixable errors are open', async () => {
            migrationApiServiceMock.getLogGroups.mockReturnValue(
                Promise.resolve({
                    levelCounts: {
                        error: 0,
                    },
                }),
            );

            migrationLoggingRepositoryMock.search.mockReturnValue(
                Promise.resolve({
                    total: 123,
                }),
            );

            const wrapper = await createWrapper();
            await flushPromises();

            await wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue').trigger('click');
            await flushPromises();

            expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(2);
            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(2);
            expect(migrationApiServiceMock.continueAfterErrorResolution).toHaveBeenCalledTimes(0);

            expect(wrapper.vm.openContinueModal).toBe(true);
            expect(wrapper.find('.swag-migration-error-resolution-step__continue-modal').exists()).toBe(true);

            expect(
                wrapper.find('.swag-migration-error-resolution-step__continue-modal-confirm').attributes('disabled'),
            ).toBeUndefined();
            await wrapper.find('.swag-migration-error-resolution-step__continue-modal-confirm').trigger('click');
            await flushPromises();

            expect(wrapper.vm.openContinueModal).toBe(false);
            expect(wrapper.find('.swag-migration-error-resolution-step__continue-modal').exists()).toBe(false);

            expect(migrationApiServiceMock.continueAfterErrorResolution).toHaveBeenCalledTimes(1);
        });

        it.each([
            {
                name: 'only fixable errors',
                fixable: 3,
                unfixable: 0,
                snippet: 'swag-migration.index.error-resolution.step.continue-modal.text-fixable',
                tooltip: 'swag-migration.index.error-resolution.step.header.continueTooltip',
                disabled: '',
            },
            {
                name: 'only unfixable errors',
                fixable: 0,
                unfixable: 2,
                snippet: 'swag-migration.index.error-resolution.step.continue-modal.text-unfixable',
                tooltip: '',
                disabled: undefined,
            },
            {
                name: 'both fixable and unfixable errors',
                fixable: 4,
                unfixable: 1,
                snippet: 'swag-migration.index.error-resolution.step.continue-modal.text-fixable',
                tooltip: 'swag-migration.index.error-resolution.step.header.continueTooltip',
                disabled: '',
            },
        ])(
            'should open continue warn modal when continuing migration with open errors: $name',
            async ({ fixable, unfixable, snippet, tooltip, disabled }) => {
                migrationApiServiceMock.getLogGroups.mockReturnValue(
                    Promise.resolve({
                        levelCounts: {
                            error: fixable,
                        },
                    }),
                );

                migrationLoggingRepositoryMock.search.mockReturnValue(
                    Promise.resolve({
                        total: unfixable,
                    }),
                );

                const wrapper = await createWrapper();
                await flushPromises();

                expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(1);
                expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(1);

                await wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue').trigger('click');
                expect(
                    wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue').attributes('disabled'),
                ).toBeDefined();
                expect(wrapper.vm.continueLoading).toBe(false);
                await flushPromises();

                expect(migrationApiServiceMock.getLogGroups).toHaveBeenCalledTimes(2);
                expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(2);
                expect(migrationApiServiceMock.continueAfterErrorResolution).toHaveBeenCalledTimes(0);

                expect(wrapper.vm.totalUnresolvedErrors).toBe(fixable);
                expect(wrapper.vm.openContinueModal).toBe(true);
                expect(wrapper.find('.swag-migration-error-resolution-step__continue-modal').exists()).toBe(true);

                expect(wrapper.find('.swag-migration-error-resolution-step__continue-modal-text').text()).toBe(snippet);
                expect(
                    wrapper.find('.swag-migration-error-resolution-step__continue-modal-confirm').attributes('disabled'),
                ).toBe(disabled);
                expect(
                    wrapper
                        .find('.swag-migration-error-resolution-step__continue-modal-confirm')
                        .attributes('tooltip-mock-message'),
                ).toBe(tooltip);

                await wrapper.find('.swag-migration-error-resolution-step__continue-modal-cancel').trigger('click');
                await flushPromises();

                expect(wrapper.vm.openContinueModal).toBe(false);
                expect(wrapper.find('.swag-migration-error-resolution-step__continue-modal').exists()).toBe(false);
            },
        );

        it('should not continue migration if unfixable error count can not be re-fetched', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            Shopware.Store.get('notification').$reset();

            migrationLoggingRepositoryMock.search.mockReturnValueOnce(Promise.reject(new Error('Fetch failed')));

            await wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue').trigger('click');
            await flushPromises();

            expect(migrationApiServiceMock.continueAfterErrorResolution).toHaveBeenCalledTimes(0);

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.fetchUnfixableErrorsFailed',
                    }),
                ]),
            );
        });

        it('should not continue migration if fixable error count can not be re-fetched', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            Shopware.Store.get('notification').$reset();

            migrationApiServiceMock.getLogGroups.mockReturnValueOnce(Promise.reject(new Error('Fetch failed')));

            await wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue').trigger('click');
            await flushPromises();

            expect(migrationApiServiceMock.continueAfterErrorResolution).toHaveBeenCalledTimes(0);

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.continueMigrationFailed',
                    }),
                ]),
            );
        });

        it('should display error notification if continue migration fails', async () => {
            migrationApiServiceMock.getLogGroups.mockReturnValue(
                Promise.resolve({
                    levelCounts: {
                        error: 0,
                    },
                }),
            );

            migrationLoggingRepositoryMock.search.mockReturnValue(
                Promise.resolve({
                    total: 0,
                }),
            );

            const wrapper = await createWrapper();
            await flushPromises();

            Shopware.Store.get('notification').$reset();

            migrationApiServiceMock.continueAfterErrorResolution.mockReturnValueOnce(
                Promise.reject(new Error('Continue failed')),
            );

            await wrapper.find('.swag-migration-error-resolution-step__header-buttons-continue').trigger('click');
            await flushPromises();

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.continueMigrationFailed',
                    }),
                ]),
            );
        });
    });
});
