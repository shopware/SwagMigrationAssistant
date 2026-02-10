/**
 * @sw-package after-sales
 */
import { mount } from '@vue/test-utils';
import SwagMigrationErrorResolutionModal, {
    ERROR_CODE_COMPONENT_MAPPING,
} from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-modal';
import SwagMigrationErrorResolutionDetailsModal from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-details-modal';
import SwagMigrationErrorResolutionField from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field';
import SwagMigrationErrorResolutionFieldScalar from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field-scalar';
import SwagMigrationErrorResolutionFieldRelation from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-field/swag-migration-error-resolution-field-relation';
import SwagMigrationErrorResolutionService from 'SwagMigrationAssistant/module/swag-migration/service/swag-migration-error-resolution.service';
import SwagMigrationDataGridExtended from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-data-grid-extended';
import { fixtureLogGroups, fixtureLogs, fixtureFixes } from '@/fixture';

const { EntityCollection } = Shopware.Data;

Shopware.Component.register('swag-migration-error-resolution-modal', SwagMigrationErrorResolutionModal);

// sub-components
Shopware.Component.register('swag-migration-error-resolution-details-modal', SwagMigrationErrorResolutionDetailsModal);
Shopware.Component.register('swag-migration-error-resolution-field', SwagMigrationErrorResolutionField);
Shopware.Component.register('swag-migration-error-resolution-field-scalar', SwagMigrationErrorResolutionFieldScalar);
Shopware.Component.register('swag-migration-error-resolution-field-relation', SwagMigrationErrorResolutionFieldRelation);

const logMocks = [
    {
        ...fixtureLogs.at(0),
        id: 'log-id-1',
        entityId: 'log-entity-id-1',
        convertedData: {
            ...fixtureLogs.at(0).convertedData,
            entityId: 'log-entity-id-1',
        },
    },
    {
        ...fixtureLogs.at(1),
        id: 'log-id-2',
        entityId: 'log-entity-id-2',
        convertedData: {
            ...fixtureLogs.at(1).convertedData,
            entityId: 'log-entity-id-2',
        },
    },
];

const fixMocks = [
    {
        ...fixtureFixes.at(0),
        entityId: 'log-entity-id-1',
    },
];

const defaultProps = {
    selectedLog: {
        ...fixtureLogGroups.at(0),
        entityName: 'media',
        fieldName: 'createdAt',
    },
    runId: 'test-run-id',
};

const migrationApiServiceMock = {
    validateResolution: jest.fn(() => Promise.resolve({ valid: true })),
    getUnresolvedLogsBatchInformation: jest.fn(() =>
        Promise.resolve({
            count: logMocks.length,
            limit: 10,
        }),
    ),
    getLogEntityIdsWithoutFix: jest.fn(() =>
        Promise.resolve({
            entityIds: logMocks.map((log) => log.entityId),
        }),
    ),
};

const migrationLoggingRepositoryMock = {
    search: jest.fn(() => {
        const result = [...logMocks];
        result.total = logMocks.length;

        return Promise.resolve(result);
    }),
};

const migrationFixRepositoryMock = {
    save: jest.fn(() => Promise.resolve()),
    delete: jest.fn(() => Promise.resolve()),
    create: jest.fn(() => ({ isNew: () => true })),
    search: jest.fn(() => Promise.resolve(fixMocks)),
    saveAll: jest.fn(() => Promise.resolve()),
};

const taxRepositoryMock = {
    search: jest.fn(() => {
        const result = [
            {
                id: 'tax-id-1',
                name: 'Standard rate',
                taxRate: 19,
            },
        ];
        result.total = 1;

        return Promise.resolve(result);
    }),
};

const repositoryFactoryMock = {
    create: (name) => {
        if (name === 'swag_migration_logging') {
            return migrationLoggingRepositoryMock;
        }

        if (name === 'swag_migration_fix') {
            return migrationFixRepositoryMock;
        }

        if (name === 'tax') {
            return taxRepositoryMock;
        }

        return null;
    },
};

async function createWrapper(props = defaultProps) {
    await wrapTestComponent('sw-data-grid');
    Shopware.Component.extend('swag-migration-data-grid-extended', 'sw-data-grid', SwagMigrationDataGridExtended);

    return mount(await Shopware.Component.build('swag-migration-error-resolution-modal'), {
        props,
        global: {
            stubs: {
                'swag-migration-error-resolution-field-relation': await Shopware.Component.build(
                    'swag-migration-error-resolution-field-relation',
                ),
                'swag-migration-error-resolution-details-modal': await Shopware.Component.build(
                    'swag-migration-error-resolution-details-modal',
                ),
                'swag-migration-error-resolution-field-scalar': await Shopware.Component.build(
                    'swag-migration-error-resolution-field-scalar',
                ),
                'swag-migration-error-resolution-field': await Shopware.Component.build(
                    'swag-migration-error-resolution-field',
                ),
                'swag-migration-data-grid-extended': await Shopware.Component.build('swag-migration-data-grid-extended'),
                'sw-entity-single-select': await wrapTestComponent('sw-entity-single-select'),
                'sw-select-result-list': await wrapTestComponent('sw-select-result-list'),
                'sw-popover-deprecated': await wrapTestComponent('sw-popover-deprecated'),
                'sw-context-menu-item': await wrapTestComponent('sw-context-menu-item'),
                'sw-context-button': await wrapTestComponent('sw-context-button'),
                'sw-select-result': await wrapTestComponent('sw-select-result'),
                'sw-context-menu': await wrapTestComponent('sw-context-menu'),
                'sw-block-field': await wrapTestComponent('sw-block-field'),
                'sw-select-base': await wrapTestComponent('sw-select-base'),
                'sw-base-field': await wrapTestComponent('sw-base-field'),
                'sw-pagination': await wrapTestComponent('sw-pagination'),
                'sw-data-grid': await wrapTestComponent('sw-data-grid'),
                'sw-provide': await wrapTestComponent('sw-provide'),
                'sw-popover': await wrapTestComponent('sw-popover'),
                'swag-migration-error-resolution-field-unhandled': true,
                'sw-data-grid-column-boolean': true,
                'sw-entity-multi-id-select': true,
                'sw-data-grid-inline-edit': true,
                'sw-product-variant-info': true,
                'sw-data-grid-settings': true,
                'sw-inheritance-switch': true,
                'sw-data-grid-skeleton': true,
                'sw-ai-copilot-badge': true,
                'sw-highlight-text': true,
                'sw-field-error': true,
                'sw-code-editor': true,
                'sw-help-text': true,
                'router-link': true,
                'sw-loader': true,
            },
            provide: {
                repositoryFactory: repositoryFactoryMock,
                migrationApiService: migrationApiServiceMock,
                swagMigrationErrorResolutionService: new SwagMigrationErrorResolutionService(),
            },
        },
    });
}

describe('module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-modal', () => {
    afterEach(() => {
        jest.clearAllMocks();
        Shopware.Store.get('notification').$reset();
    });

    describe('constants', () => {
        it('should provide error code to component mapping', () => {
            expect(ERROR_CODE_COMPONENT_MAPPING).toStrictEqual({
                SWAG_MIGRATION_VALIDATION_OPTIONAL_FIELD_VALUE_INVALID: 'DEFAULT',
                SWAG_MIGRATION_VALIDATION_REQUIRED_FIELD_VALUE_INVALID: 'DEFAULT',
                SWAG_MIGRATION_VALIDATION_REQUIRED_FIELD_MISSING: 'DEFAULT',
            });
        });
    });

    describe('selectedCount', () => {
        it('should display selectedLogIds length when selectAllMode is inactive', async () => {
            migrationFixRepositoryMock.search.mockReturnValueOnce(Promise.resolve([]));

            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.find('.sw-data-grid__bulk-selected-count').exists()).toBe(false);

            const rowCheckboxes = wrapper.findAll('.sw-data-grid__body .mt-field--checkbox input');
            await rowCheckboxes[0].setChecked(true);
            await flushPromises();

            expect(wrapper.find('.sw-data-grid__bulk-selected-count').text()).toBe('1');
        });

        it('should display tableTotal in selectAllMode and persist across page navigation', async () => {
            const defaultLoggingSearch = migrationLoggingRepositoryMock.search.getMockImplementation();
            const defaultFixSearch = migrationFixRepositoryMock.search.getMockImplementation();

            const paginatedLogs = Array.from({ length: 28 }, (_, i) => ({
                ...logMocks[0],
                id: `log-id-${i + 1}`,
                entityId: `log-entity-id-${i + 1}`,
            }));

            migrationLoggingRepositoryMock.search.mockImplementation((criteria) => {
                const page = criteria.page;
                const limit = criteria.limit;
                const start = (page - 1) * limit;
                const end = start + limit;

                const result = paginatedLogs.slice(start, end);
                result.total = paginatedLogs.length;

                return Promise.resolve(result);
            });
            migrationFixRepositoryMock.search.mockImplementation(() => Promise.resolve([]));

            const wrapper = await createWrapper();
            await flushPromises();

            const rowCheckboxes = wrapper.findAll('.sw-data-grid__body .mt-field--checkbox input');
            await rowCheckboxes[0].setChecked(true);
            await flushPromises();

            const selectAllButton = wrapper.find('.sw-data-grid__bulk .bulk-link button');
            await selectAllButton.trigger('click');
            await flushPromises();

            expect(wrapper.find('.sw-data-grid__row--0 .mt-field--checkbox input').attributes('disabled')).toBeDefined();
            expect(wrapper.find('.sw-data-grid__bulk-selected-count').text()).toBe('28');

            await wrapper.find('.sw-pagination__page-button-next').trigger('click');
            await flushPromises();

            expect(wrapper.find('.sw-data-grid__bulk-selected-count').text()).toBe('28');

            migrationLoggingRepositoryMock.search.mockImplementation(defaultLoggingSearch);
            migrationFixRepositoryMock.search.mockImplementation(defaultFixSearch);
        });
    });

    describe('initial loading', () => {
        it('should load initial data when modal is opened', async () => {
            const wrapper = await createWrapper();

            expect(wrapper.vm.loading).toBe(true);
            expect(wrapper.findComponent('.swag-migration-error-resolution-modal__left-grid').props('isLoading')).toBe(true);
            expect(
                wrapper.find('.swag-migration-error-resolution-modal__right-content-button').attributes('disabled'),
            ).toBeDefined();

            await flushPromises();

            expect(wrapper.vm.loading).toBe(false);
            expect(wrapper.findComponent('.swag-migration-error-resolution-modal__left-grid').props('isLoading')).toBe(
                false,
            );

            expect(migrationLoggingRepositoryMock.search).toHaveBeenNthCalledWith(
                1,
                expect.objectContaining({
                    limit: 25,
                    page: 1,
                    filters: [
                        { type: 'equals', field: 'runId', value: defaultProps.runId },
                        { type: 'equals', field: 'code', value: defaultProps.selectedLog.code },
                        { type: 'equals', field: 'entityName', value: defaultProps.selectedLog.entityName },
                        { type: 'equals', field: 'fieldName', value: defaultProps.selectedLog.fieldName },
                    ],
                }),
            );

            expect(wrapper.vm.tableTotal).toBe(logMocks.length);

            expect(migrationFixRepositoryMock.search).toHaveBeenNthCalledWith(
                1,
                expect.objectContaining({
                    filters: [
                        { type: 'equals', field: 'connectionId', value: null },
                        { type: 'equals', field: 'entityName', value: defaultProps.selectedLog.entityName },
                        { type: 'equals', field: 'path', value: defaultProps.selectedLog.fieldName },
                        { type: 'equalsAny', field: 'entityId', value: expect.any(String) },
                    ],
                }),
            );

            expect(new Array(wrapper.vm.tableData)).toStrictEqual([
                expect.arrayContaining([
                    expect.objectContaining({
                        logId: expect.any(String),
                        entityId: expect.any(String),
                        status: expect.any(Boolean),
                        convertedData: expect.any(Object),
                        sourceData: expect.any(Object),
                    }),
                ]),
            ]);

            expect(wrapper.vm.tableData.at(0)[defaultProps.selectedLog.fieldName]).toBe(fixMocks.at(0).value);
        });

        it('should display error notification when loading data fails', async () => {
            migrationLoggingRepositoryMock.search.mockReturnValueOnce(Promise.reject(new Error('failed to load logs')));
            Shopware.Store.get('notification').$reset();

            await createWrapper();
            await flushPromises();

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.fetchLogsFailed',
                    }),
                ]),
            );
        });

        it('should display existing fixes for selected log as resolved', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            const statusColumns = wrapper.findAll('.swag-migration-error-resolution-modal__left-status');
            expect(statusColumns).toHaveLength(logMocks.length);

            expect(wrapper.findAll('.swag-migration-error-resolution-modal__left-status--unresolved')).toHaveLength(1);
            expect(wrapper.findAll('.swag-migration-error-resolution-modal__left-status--resolved')).toHaveLength(1);

            expect(statusColumns.map((col) => col.text())).toStrictEqual([
                'swag-migration.index.error-resolution.modals.error.left.status.resolved',
                'swag-migration.index.error-resolution.modals.error.left.status.unresolved',
            ]);
        });

        it('should not load existing fixes if no logs were found', async () => {
            migrationLoggingRepositoryMock.search.mockReturnValueOnce(
                Promise.resolve(new EntityCollection(null, 'swag_migration_logging', null, null, [], 0)),
            );

            const wrapper = await createWrapper();
            await flushPromises();

            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalled();
            expect(migrationFixRepositoryMock.search).not.toHaveBeenCalled();

            expect(wrapper.findAll('.sw-data-grid__row')).toHaveLength(1); // 1 for header row
        });

        it('should not apply existing fixes when fetching fixes fails', async () => {
            migrationFixRepositoryMock.search.mockReturnValueOnce(Promise.reject(new Error('failed to load fixes')));
            Shopware.Store.get('notification').$reset();

            await createWrapper();
            await flushPromises();

            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalled();
            expect(migrationFixRepositoryMock.search).toHaveBeenCalled();

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.fetchExistingFixesFailed',
                    }),
                ]),
            );
        });
    });

    describe('table interactions', () => {
        it('should display generated table columns', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            const headerCells = wrapper.findAll('.sw-data-grid__cell--header');

            // 5 default + 2 for select row & spacer
            expect(headerCells.filter((cell) => cell.isVisible())).toHaveLength(7);
            expect(headerCells).toHaveLength(22);

            expect(headerCells.map((cell) => cell.text())).toStrictEqual([
                '', // select row
                'swag-migration.index.error-resolution.modals.error.table.columns.status',
                'createdAt', // field name of the log
                'id',
                'title',
                'alt',
                'url',
                'path',
                'fileExtension',
                'fileHash',
                'fileName',
                'fileSize',
                'hasFile',
                'mediaFolderId',
                'mediaTypeRaw',
                'mimeType',
                'private',
                'thumbnailsRo',
                'updatedAt',
                'uploadedAt',
                'userId',
                '', // spacer
            ]);
        });

        it('should open & close details modal when clicking on details action', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            await wrapper.find('.sw-data-grid__row--0 .sw-data-grid__cell--actions button').trigger('click');
            await flushPromises();

            expect(wrapper.find('.swag-migration-error-resolution-details-modal').exists()).toBe(false);

            expect(wrapper.find('.sw-context-button__menu-popover').exists()).toBe(true);
            await wrapper.find('.swag-migration-error-resolution-modal__left-modal-action-details').trigger('click');

            expect(wrapper.find('.swag-migration-error-resolution-details-modal').exists()).toBe(true);
            expect(wrapper.vm.selectedDetailsLog).toStrictEqual(wrapper.vm.tableData.at(0));

            await wrapper.find('.swag-migration-error-resolution-details-modal').trigger('modal-close');
            await flushPromises();

            expect(wrapper.find('.swag-migration-error-resolution-details-modal').exists()).toBe(false);
            expect(wrapper.vm.selectedDetailsLog).toBeNull();
        });

        it('should be able to reset resolved log if log has fix', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.findAll('.swag-migration-error-resolution-modal__left-status--resolved')).toHaveLength(1);
            expect(wrapper.findAll('.swag-migration-error-resolution-modal__left-status--unresolved')).toHaveLength(1);

            await wrapper.find('.sw-data-grid__row--1 .sw-data-grid__cell--actions button').trigger('click');
            await flushPromises();

            expect(wrapper.find('.swag-migration-error-resolution-modal__left-modal-action-reset').exists()).toBe(false);

            await wrapper.find('.sw-data-grid__row--0 .sw-data-grid__cell--actions button').trigger('click');
            await flushPromises();

            migrationFixRepositoryMock.search.mockReturnValueOnce(Promise.resolve([]));

            expect(wrapper.find('.sw-context-button__menu-popover').exists()).toBe(true);
            await wrapper.find('.swag-migration-error-resolution-modal__left-modal-action-reset').trigger('click');

            await flushPromises();

            expect(migrationFixRepositoryMock.delete).toHaveBeenCalledWith(fixMocks.at(0).id);
            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(2);

            expect(wrapper.findAll('.swag-migration-error-resolution-modal__left-status--resolved')).toHaveLength(0);
            expect(wrapper.findAll('.swag-migration-error-resolution-modal__left-status--unresolved')).toHaveLength(2);
        });

        it('should not be able to select logs that are already resolved', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.find('.sw-data-grid__row--0 .mt-field--checkbox input').attributes('disabled')).toBeDefined();
            expect(wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').attributes('disabled')).toBeUndefined();
        });

        it('should be able to navigate through pages and change page size', async () => {
            const originalSearchMock = migrationLoggingRepositoryMock.search.getMockImplementation();

            migrationLoggingRepositoryMock.search.mockImplementation(() => {
                const result = [
                    ...new Array(26).fill(null).map((_, index) => ({
                        ...logMocks.at(0),
                        id: `log-id-${index + 1}`,
                        entityId: `log-entity-id-${index + 1}`,
                        convertedData: {
                            ...logMocks.at(0).convertedData,
                            entityId: `log-entity-id-${index + 1}`,
                        },
                    })),
                ];
                result.total = 26;

                return Promise.resolve(result);
            });

            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.find('.sw-pagination').exists()).toBe(true);

            expect(wrapper.findAll('.sw-data-grid__body .sw-data-grid__row')).toHaveLength(26);
            expect(wrapper.find('.sw-pagination__per-page input').element.value).toBe('25');
            expect(wrapper.find('.sw-pagination__list .is-active').text()).toBe('1');

            await wrapper.find('.sw-pagination__page-button-next').trigger('click');
            await flushPromises();

            expect(wrapper.findAll('.sw-data-grid__row')).toHaveLength(27);
            expect(wrapper.find('.sw-pagination__list-item .is-active').text()).toBe('2');

            migrationLoggingRepositoryMock.search.mockImplementation(originalSearchMock);
        });

        it('should select and disable all checkboxes when clicking "Select All" and reset selectAllMode when click deselect-all button', async () => {
            // ensure no logs are already resolved at start
            migrationFixRepositoryMock.search.mockReturnValueOnce(Promise.resolve([]));

            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.vm.selectAllMode).toBe(false);
            expect(wrapper.vm.selectedLogIds).toHaveLength(0);
            expect(wrapper.findAll('.sw-data-grid__body .sw-data-grid__row')).toHaveLength(logMocks.length);
            expect(wrapper.findAll('.swag-migration-error-resolution-modal__left-status--unresolved')).toHaveLength(
                logMocks.length,
            );
            expect(wrapper.findAll('.sw-data-grid__body .mt-field--checkbox input[checked]')).toHaveLength(0);

            const rowCheckboxes = wrapper.findAll('.sw-data-grid__body .mt-field--checkbox input');
            expect(rowCheckboxes).toHaveLength(logMocks.length);

            await rowCheckboxes[0].setChecked(true);
            await flushPromises();

            expect(wrapper.vm.selectAllMode).toBe(false);
            expect(wrapper.vm.selectedLogIds).toHaveLength(1);

            const selectAllButton = wrapper.find('.sw-data-grid__bulk .bulk-link button');
            expect(selectAllButton.exists()).toBe(true);

            await selectAllButton.trigger('click');
            await flushPromises();

            expect(wrapper.vm.selectAllMode).toBe(true);
            expect(wrapper.vm.selectedLogIds).toHaveLength(0);

            expect(wrapper.findAll('.sw-data-grid__body .mt-field--checkbox input[checked]')).toHaveLength(logMocks.length);
            expect(wrapper.findAll('.sw-data-grid__body .mt-field--checkbox input[disabled]')).toHaveLength(logMocks.length);

            const deselectAllButton = wrapper.find('.sw-data-grid__bulk .bulk-link .bulk-deselect-all');
            expect(deselectAllButton.exists()).toBe(true);

            await deselectAllButton.trigger('click');
            await flushPromises();

            expect(wrapper.vm.selectAllMode).toBe(false);
            expect(wrapper.vm.selectedLogIds).toHaveLength(0);
            expect(wrapper.findAll('.sw-data-grid__body .mt-field--checkbox input[checked]')).toHaveLength(0);
            expect(wrapper.findAll('.sw-data-grid__body .mt-field--checkbox input[disabled]')).toHaveLength(0);
        });

        it('should preselect other page logs when selecting all logs', async () => {
            const originalSearchMock = migrationLoggingRepositoryMock.search.getMockImplementation();

            const largeLogMocks = [
                ...new Array(30).fill(null).map((_, index) => ({
                    ...logMocks.at(0),
                    id: `log-id-${index + 1}`,
                    entityId: `log-entity-id-${index + 1}`,
                    convertedData: {
                        ...logMocks.at(0).convertedData,
                        entityId: `log-entity-id-${index + 1}`,
                    },
                })),
            ];

            migrationLoggingRepositoryMock.search.mockImplementation((criteria) => {
                const page = criteria.page;
                const limit = criteria.limit;
                const start = (page - 1) * limit;
                const end = start + limit;

                const result = largeLogMocks.slice(start, end);
                result.total = largeLogMocks.length;

                return Promise.resolve(result);
            });

            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.findAll('.sw-data-grid__body .sw-data-grid__row')).toHaveLength(25);

            await wrapper.find('.sw-data-grid__header .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            expect(wrapper.vm.selectedLogIds).toHaveLength(24); // -1 for resolved log

            await wrapper.find('.sw-data-grid__header .mt-field--checkbox input').setChecked(false);
            await flushPromises();

            expect(wrapper.vm.selectedLogIds).toHaveLength(0);

            await wrapper.find('.sw-data-grid__header .mt-field--checkbox input').setChecked(true);
            await wrapper.find('.swag-migration-error-resolution-step__header-content-link').trigger('click');
            await flushPromises();

            expect(wrapper.vm.selectAllMode).toBe(true);
            expect(wrapper.vm.selectedLogIds).toHaveLength(0);

            expect(wrapper.findAll('.sw-data-grid__body .mt-field--checkbox input[checked]')).toHaveLength(24);
            await wrapper.find('.sw-pagination__page-button-next').trigger('click');
            await flushPromises();

            expect(wrapper.findAll('.sw-data-grid__body .mt-field--checkbox input[checked]')).toHaveLength(5);

            migrationLoggingRepositoryMock.search.mockImplementation(originalSearchMock);
        });
    });

    describe('create resolution fix', () => {
        it('should save fix when backend validation passes', async () => {
            migrationApiServiceMock.validateResolution.mockResolvedValueOnce({ valid: true, violations: [] });

            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            await inputField.setValue('Valid Title');
            await flushPromises();

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(migrationApiServiceMock.validateResolution).toHaveBeenCalledWith('media', 'title', 'Valid Title');
            expect(wrapper.vm.fieldError).toBeNull();
            expect(migrationFixRepositoryMock.saveAll).toHaveBeenCalled();
        });

        it('should not save fix when backend validation fails without message', async () => {
            migrationApiServiceMock.validateResolution.mockResolvedValueOnce({ valid: false, violations: [] });

            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            await inputField.setValue('Invalid Value');
            await flushPromises();

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(migrationApiServiceMock.validateResolution).toHaveBeenCalledWith('media', 'title', 'Invalid Value');
            expect(wrapper.vm.fieldError).toBeNull();
            expect(migrationFixRepositoryMock.saveAll).not.toHaveBeenCalled();
        });

        it('should display field error when backend validation fails with message', async () => {
            migrationApiServiceMock.validateResolution.mockResolvedValueOnce({
                valid: false,
                violations: [{ message: 'This value is invalid.' }],
            });

            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            await inputField.setValue('Invalid Value');
            await flushPromises();

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(migrationApiServiceMock.validateResolution).toHaveBeenCalledWith('media', 'title', 'Invalid Value');
            expect(wrapper.vm.fieldError).toStrictEqual({ detail: 'This value is invalid.' });
            expect(migrationFixRepositoryMock.saveAll).not.toHaveBeenCalled();
        });

        it.each(Object.keys(ERROR_CODE_COMPONENT_MAPPING).map((code) => ({ code })))(
            'should render default resolve component for defined codes: $code',
            async ({ code }) => {
                const wrapper = await createWrapper({
                    ...defaultProps,
                    selectedLog: {
                        ...fixtureLogGroups.at(1),
                        code: code,
                        entityName: 'media',
                        fieldName: 'title',
                    },
                });
                await flushPromises();

                expect(wrapper.find('.swag-migration-error-resolution-modal__right-content-default').exists()).toBe(true);
            },
        );

        it('should render unresolvable field component for unsupported error codes', async () => {
            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    code: 'SOME_UNSUPPORTED_ERROR_CODE',
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            expect(wrapper.find('.swag-migration-error-resolution-modal__right-content-unresolvable').exists()).toBe(true);
        });

        it('should disable input & create button when no logs are selected', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.find('.swag-migration-error-resolution-field input').attributes('disabled')).toBeDefined();
            expect(
                wrapper.find('.swag-migration-error-resolution-modal__right-content-button').attributes('disabled'),
            ).toBeDefined();

            await wrapper.find('.sw-data-grid__header .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            expect(wrapper.find('.swag-migration-error-resolution-field input').attributes('disabled')).toBeUndefined();
            expect(
                wrapper.find('.swag-migration-error-resolution-modal__right-content-button').attributes('disabled'),
            ).toBeUndefined();
        });

        it('should be able to create a resolution fixes for a scalar field', async () => {
            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            expect(inputField.exists()).toBe(true);

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            await inputField.setValue('New Title');
            await flushPromises();
            expect(wrapper.vm.fieldValue).toBe('New Title');

            expect(
                wrapper.find('.swag-migration-error-resolution-modal__right-content-button').attributes('disabled'),
            ).toBeUndefined();

            migrationLoggingRepositoryMock.search.mockClear();

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(migrationFixRepositoryMock.create).toHaveBeenCalledWith();
            expect(migrationFixRepositoryMock.saveAll).toHaveBeenCalledWith([
                expect.objectContaining({
                    entityName: 'media',
                    path: 'title',
                    entityId: 'log-entity-id-2',
                    value: 'New Title',
                }),
            ]);

            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(1);
            expect(wrapper.emitted()).toHaveProperty('fixes-created');
        });

        it('should be able to create a resolution fixes for a relation field', async () => {
            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'product',
                    fieldName: 'taxId',
                },
            });
            await flushPromises();

            const relationField = wrapper.find('.swag-migration-error-resolution-field-relation');
            expect(relationField.exists()).toBe(true);

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            await relationField.find('.sw-entity-single-select__selection').trigger('click');
            await flushPromises();

            await relationField.find('.sw-select-result').trigger('click');
            await flushPromises();

            expect(wrapper.vm.fieldValue).toBe('tax-id-1');

            expect(
                wrapper.find('.swag-migration-error-resolution-modal__right-content-button').attributes('disabled'),
            ).toBeUndefined();

            migrationLoggingRepositoryMock.search.mockClear();

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(migrationFixRepositoryMock.create).toHaveBeenCalledWith();
            expect(migrationFixRepositoryMock.saveAll).toHaveBeenCalledWith([
                expect.objectContaining({
                    entityName: 'product',
                    entityId: 'log-entity-id-2',
                    path: 'taxId',
                    value: 'tax-id-1',
                }),
            ]);

            expect(migrationLoggingRepositoryMock.search).toHaveBeenCalledTimes(1);
            expect(wrapper.emitted()).toHaveProperty('fixes-created');
        });

        it('should display error notification when saving manually selected fixes fails', async () => {
            migrationFixRepositoryMock.saveAll.mockRejectedValueOnce(new Error('failed to save fixes'));
            Shopware.Store.get('notification').$reset();

            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            expect(inputField.exists()).toBe(true);

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            await inputField.setValue('New Title');
            await flushPromises();
            expect(wrapper.vm.fieldValue).toBe('New Title');

            expect(
                wrapper.find('.swag-migration-error-resolution-modal__right-content-button').attributes('disabled'),
            ).toBeUndefined();

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(wrapper.vm.submitLoading).toBe(false);

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.submitResolutionFailed',
                    }),
                ]),
            );
        });

        it('should display error notification when saving fixes in batches fails', async () => {
            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });

            const unresolvedLogsCount = 12;
            const limit = 5;
            migrationApiServiceMock.getUnresolvedLogsBatchInformation.mockResolvedValueOnce({
                count: unresolvedLogsCount,
                limit: limit,
            });

            // second batch save will fail
            // third batch should never be called
            migrationApiServiceMock.getLogEntityIdsWithoutFix
                .mockResolvedValueOnce({
                    entityIds: Array.from({ length: limit }, (_, i) => `entity-ids-batch-1-${i + 1}`),
                })
                .mockResolvedValueOnce({
                    entityIds: Array.from({ length: limit }, (_, i) => `entity-ids-batch-2-${i + 1}`),
                })
                .mockResolvedValueOnce({
                    entityIds: Array.from({ length: 2 }, (_, i) => `entity-ids-batch-3-${i + 1}`),
                });

            migrationFixRepositoryMock.saveAll
                .mockResolvedValueOnce('first batch saved successfully')
                .mockRejectedValueOnce(new Error('failed to save second batch'))
                .mockResolvedValueOnce('third batch should never be called');

            migrationFixRepositoryMock.saveAll.mockRejectedValueOnce(new Error('failed to save fixes'));
            Shopware.Store.get('notification').$reset();

            await flushPromises();

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            const selectAllButton = wrapper.find('.sw-data-grid__bulk .bulk-link button');
            expect(selectAllButton.exists()).toBe(true);

            await selectAllButton.trigger('click');
            await flushPromises();

            expect(wrapper.vm.selectAllMode).toBe(true);

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            expect(inputField.exists()).toBe(true);

            await inputField.setValue('New Title');
            await flushPromises();
            expect(wrapper.vm.fieldValue).toBe('New Title');

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(wrapper.vm.submitLoading).toBe(false);

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(migrationApiServiceMock.getUnresolvedLogsBatchInformation).toHaveBeenCalledTimes(1);
            expect(migrationApiServiceMock.getLogEntityIdsWithoutFix).toHaveBeenCalledTimes(2);
            expect(migrationFixRepositoryMock.saveAll).toHaveBeenCalledTimes(2);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.submitResolutionFailed',
                    }),
                ]),
            );
        });

        it('should display error notification when validating a fix fails', async () => {
            Shopware.Store.get('notification').$reset();

            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            expect(inputField.exists()).toBe(true);

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            await inputField.setValue(''); // invalid
            await flushPromises();
            expect(wrapper.vm.fieldValue).toBe('');

            expect(
                wrapper.find('.swag-migration-error-resolution-modal__right-content-button').attributes('disabled'),
            ).toBeUndefined();

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(wrapper.vm.submitLoading).toBe(false);

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.fieldValueNotSet',
                    }),
                ]),
            );
        });

        it('should display error notification when no entity ids could be extracted for submission', async () => {
            Shopware.Store.get('notification').$reset();

            migrationLoggingRepositoryMock.search.mockImplementationOnce(() => {
                const result = [
                    {
                        ...logMocks.at(1),
                        entityId: null, // invalid
                    },
                ];
                result.total = 1;

                return Promise.resolve(result);
            });

            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            expect(inputField.exists()).toBe(true);

            await wrapper.find('.sw-data-grid__row--0 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            await inputField.setValue('New Title');
            await flushPromises();
            expect(wrapper.vm.fieldValue).toBe('New Title');

            expect(
                wrapper.find('.swag-migration-error-resolution-modal__right-content-button').attributes('disabled'),
            ).toBeUndefined();

            migrationLoggingRepositoryMock.search.mockClear();

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(wrapper.vm.submitLoading).toBe(false);

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.noEntityIdsFound',
                    }),
                ]),
            );
        });

        it('should call submitResolutionForSelectedIds when rows are selected manually', async () => {
            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            const submitResolutionForSelectedIdsSpy = jest.spyOn(wrapper.vm, 'submitResolutionForSelectedIds');
            const submitResolutionInBatchesSpy = jest.spyOn(wrapper.vm, 'submitResolutionInBatches');

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            expect(inputField.exists()).toBe(true);

            await inputField.setValue('New Title');
            await flushPromises();
            expect(wrapper.vm.fieldValue).toBe('New Title');

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(submitResolutionInBatchesSpy).not.toHaveBeenCalled();
            expect(submitResolutionForSelectedIdsSpy).toHaveBeenCalledTimes(1);
            expect(migrationApiServiceMock.getLogEntityIdsWithoutFix).not.toHaveBeenCalled();
        });

        it('should call submitResolutionInBatches when selectAllMode is active', async () => {
            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            const submitResolutionForSelectedIdsSpy = jest.spyOn(wrapper.vm, 'submitResolutionForSelectedIds');
            const submitResolutionInBatchesSpy = jest.spyOn(wrapper.vm, 'submitResolutionInBatches');

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            const selectAllButton = wrapper.find('.sw-data-grid__bulk .bulk-link button');
            expect(selectAllButton.exists()).toBe(true);

            await selectAllButton.trigger('click');
            await flushPromises();

            expect(wrapper.vm.selectAllMode).toBe(true);

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            expect(inputField.exists()).toBe(true);

            await inputField.setValue('New Title');
            await flushPromises();
            expect(wrapper.vm.fieldValue).toBe('New Title');

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(submitResolutionForSelectedIdsSpy).not.toHaveBeenCalled();
            expect(submitResolutionInBatchesSpy).toHaveBeenCalledTimes(1);
            expect(migrationApiServiceMock.getLogEntityIdsWithoutFix).toHaveBeenCalledTimes(1);
        });

        it('should fetch entityIds from swag_migration_logging in batches', async () => {
            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            const unresolvedLogsCount = 7;
            const limit = 5;
            migrationApiServiceMock.getUnresolvedLogsBatchInformation.mockResolvedValueOnce({
                count: unresolvedLogsCount,
                limit: limit,
            });

            migrationApiServiceMock.getLogEntityIdsWithoutFix
                .mockResolvedValueOnce({
                    entityIds: Array.from({ length: limit }, (_, i) => `entity-ids-batch-1-${i + 1}`),
                })
                .mockResolvedValueOnce({
                    entityIds: Array.from({ length: 2 }, (_, i) => `entity-ids-batch-2-${i + 1}`),
                });

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await flushPromises();

            const selectAllButton = wrapper.find('.sw-data-grid__bulk .bulk-link button');
            expect(selectAllButton.exists()).toBe(true);

            await selectAllButton.trigger('click');
            await flushPromises();

            expect(wrapper.vm.selectAllMode).toBe(true);

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            expect(inputField.exists()).toBe(true);

            await inputField.setValue('New Title');
            await flushPromises();
            expect(wrapper.vm.fieldValue).toBe('New Title');

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(migrationApiServiceMock.getUnresolvedLogsBatchInformation).toHaveBeenCalledTimes(1);
            expect(migrationApiServiceMock.getUnresolvedLogsBatchInformation).toHaveBeenLastCalledWith(
                defaultProps.runId,
                wrapper.vm.selectedLog.code,
                wrapper.vm.selectedLog.entityName,
                wrapper.vm.selectedLog.fieldName,
                null,
            );
            expect(migrationApiServiceMock.getLogEntityIdsWithoutFix).toHaveBeenCalledTimes(2);
            // just checking the last call because parameters are the same for all calls
            expect(migrationApiServiceMock.getLogEntityIdsWithoutFix).toHaveBeenLastCalledWith(
                defaultProps.runId,
                wrapper.vm.selectedLog.code,
                wrapper.vm.selectedLog.entityName,
                wrapper.vm.selectedLog.fieldName,
                limit,
                null,
            );
            expect(migrationFixRepositoryMock.saveAll).toHaveBeenCalledTimes(2);
        });

        it('should display error notification when fetching entityIds fails', async () => {
            migrationApiServiceMock.getLogEntityIdsWithoutFix.mockImplementationOnce(() => {
                return Promise.reject(new Error('failed to fetch entity ids from swag_migration_logging'));
            });
            Shopware.Store.get('notification').$reset();

            const wrapper = await createWrapper({
                ...defaultProps,
                selectedLog: {
                    ...fixtureLogGroups.at(1),
                    entityName: 'media',
                    fieldName: 'title',
                },
            });
            await flushPromises();

            await wrapper.find('.sw-data-grid__header .mt-field--checkbox input').setChecked(true);
            await wrapper.find('.swag-migration-error-resolution-step__header-content-link').trigger('click');
            await flushPromises();

            await wrapper.find('.sw-data-grid__row--1 .mt-field--checkbox input').setChecked(true);
            await wrapper.find('.sw-data-grid__bulk .bulk-link button').trigger('click');
            await flushPromises();

            const inputField = wrapper.find('.swag-migration-error-resolution-field-scalar input');
            await inputField.setValue('New Title');
            await flushPromises();

            await wrapper.find('.swag-migration-error-resolution-modal__right-content-button').trigger('click');
            await flushPromises();

            expect(wrapper.vm.submitLoading).toBe(false);

            const notifications = Object.values(Shopware.Store.get('notification').notifications);

            expect(notifications).toHaveLength(1);
            expect(notifications).toStrictEqual(
                expect.arrayContaining([
                    expect.objectContaining({
                        message: 'swag-migration.index.error-resolution.errors.submitResolutionFailed',
                    }),
                ]),
            );
        });
    });
});
