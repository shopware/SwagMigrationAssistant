/**
 * @package after-sales
 */
import { fixturePreMapping, fixtureEnvironmentInformation, fixtureDataSelection } from '@/fixture';

const defaultStoreData = {
    isLoading: false,
    dataSelectionIds: [fixtureDataSelection[0].id],
    isResettingChecksum: false,
    isTruncatingMigration: false,
    premapping: fixturePreMapping,
    dataSelectionTableData: fixtureDataSelection,
    environmentInformation: fixtureEnvironmentInformation,
};

const PREMAPPING_VALIDATION_TESTS = [
    {
        name: 'empty',
        expected: true,
        premapping: [],
    },
    {
        name: 'valid',
        expected: true,
        premapping: fixturePreMapping,
    },
    {
        name: 'destinationId = null',
        expected: false,
        premapping: [
            {
                ...fixturePreMapping[0],
                mapping: [
                    {
                        ...fixturePreMapping[0].mapping[0],
                        destinationUuid: null,
                    },
                ],
            },
        ],
    },
    {
        name: "destinationId = ''",
        expected: false,
        premapping: [
            {
                ...fixturePreMapping[0],
                mapping: [
                    {
                        ...fixturePreMapping[0].mapping[0],
                        destinationUuid: '',
                    },
                ],
            },
        ],
    },
];

const CURRENCY_MISMATCH_TESTS = [
    {
        name: 'mismatch',
        environmentInformation: {
            ...fixtureEnvironmentInformation,
            sourceSystemCurrency: 'USD',
            targetSystemCurrency: 'EUR',
        },
        expected: true,
    },
    {
        name: 'no mismatch',
        environmentInformation: {
            ...fixtureEnvironmentInformation,
            sourceSystemCurrency: 'EUR',
            targetSystemCurrency: 'EUR',
        },
        expected: false,
    },
];

const LANGUAGE_MISMATCH_TESTS = [
    {
        name: 'mismatch',
        environmentInformation: {
            ...fixtureEnvironmentInformation,
            sourceSystemLocale: 'de-DE',
            targetSystemLocale: 'en-GB',
        },
        expected: true,
    },
    {
        name: 'no mismatch',
        environmentInformation: {
            ...fixtureEnvironmentInformation,
            sourceSystemLocale: 'de-DE',
            targetSystemLocale: 'de-DE',
        },
        expected: false,
    },
];

const MIGRATION_ALLOWED_TESTS = [
    {
        name: 'currency mismatch',
        environmentInformation: {
            ...fixtureEnvironmentInformation,
            sourceSystemCurrency: 'USD',
            targetSystemCurrency: 'EUR',
        },
        warningConfirmed: false,
        isLoading: false,
        expected: false,
    },
    {
        name: 'language mismatch',
        environmentInformation: {
            ...fixtureEnvironmentInformation,
            sourceSystemLocale: 'de-DE',
            targetSystemLocale: 'en-GB',
        },
        warningConfirmed: false,
        isLoading: false,
        expected: false,
    },
    {
        name: 'no mismatch, unconfirmed',
        environmentInformation: fixtureEnvironmentInformation,
        warningConfirmed: false,
        isLoading: true,
        expected: false,
    },
    {
        name: 'no mismatch, confirmed',
        environmentInformation: fixtureEnvironmentInformation,
        warningConfirmed: true,
        isLoading: false,
        expected: true,
    },
];

const CONTINUE_ALLOWED_TESTS = [
    {
        name: 'all valid',
        expected: true,
        disabledMessage: null,
        aclRight: true,
        storeData: {},
    },
    {
        name: 'no data',
        expected: false,
        disabledMessage: 'swag-migration.general.disabledMessages.noData',
        aclRight: true,
        storeData: {
            dataSelectionTableData: [],
        },
    },
    {
        name: 'is resetting',
        expected: false,
        disabledMessage: 'swag-migration.general.disabledMessages.resettingChecksum',
        aclRight: true,
        storeData: {
            isResettingChecksum: true,
        },
    },
    {
        name: 'is truncating',
        expected: false,
        disabledMessage: 'swag-migration.general.disabledMessages.truncatingMigration',
        aclRight: true,
        storeData: {
            isTruncatingMigration: true,
        },
    },
    {
        name: 'no selected data',
        expected: false,
        disabledMessage: 'swag-migration.general.disabledMessages.noSelectedData',
        aclRight: true,
        storeData: {
            dataSelectionIds: [],
        },
    },
    {
        name: 'no selected data with optional requiredSelection false',
        expected: false,
        disabledMessage: 'swag-migration.general.disabledMessages.noSelectedData',
        aclRight: true,
        storeData: {
            dataSelectionTableData: [
                {
                    id: 'optional-selection',
                    requiredSelection: false,
                },
            ],
            dataSelectionIds: ['other-id'],
        },
    },
    {
        name: 'disabled migration',
        expected: false,
        disabledMessage: 'swag-migration.general.disabledMessages.disabled',
        aclRight: true,
        storeData: {
            environmentInformation: {
                ...fixtureEnvironmentInformation,
                migrationDisabled: true,
            },
        },
    },
    {
        name: 'is loading',
        expected: false,
        disabledMessage: 'swag-migration.general.disabledMessages.loading',
        aclRight: true,
        storeData: {
            isLoading: true,
        },
    },
    {
        name: 'in valid premapping',
        expected: false,
        disabledMessage: 'swag-migration.general.disabledMessages.unfilledPremapping',
        aclRight: true,
        storeData: {
            premapping: [
                {
                    ...fixturePreMapping[0],
                    mapping: [
                        {
                            ...fixturePreMapping[0].mapping[0],
                            destinationUuid: null,
                        },
                    ],
                },
            ],
        },
    },
    {
        name: 'no permission',
        expected: false,
        disabledMessage: 'swag-migration.general.disabledMessages.noPermission',
        aclRight: false,
        storeData: {},
    },
];

const SET_PREMAPPING_TESTS = [
    {
        name: 'reset premapping with undefined',
        initialPremapping: [],
        premapping: undefined,
        expected: [],
    },
    {
        name: 'reset premapping with null',
        initialPremapping: [],
        premapping: null,
        expected: [],
    },
    {
        name: 'reset premapping with empty array',
        initialPremapping: [],
        premapping: [],
        expected: [],
    },
    {
        name: 'set premapping with values',
        initialPremapping: fixturePreMapping,
        premapping: [
            {
                entity: fixturePreMapping[0].entity,
                choices: [
                    fixturePreMapping[0].choices[0],
                    {
                        uuid: 'test-choice',
                    },
                ],
                mapping: [
                    {
                        ...fixturePreMapping[0].mapping[0],
                        destinationUuid: null,
                    },
                    {
                        ...fixturePreMapping[0].mapping[1],
                        destinationUuid: 'test',
                    },
                    {
                        uuid: 'new-mapping-set-destination',
                        sourceId: 'new-source-id',
                        destinationUuid: 'test-mapping-1',
                    },
                    {
                        uuid: 'new-mapping-unset-destination',
                        sourceId: 'new-source-id',
                        destinationUuid: null,
                    },
                ],
            },
            {
                entity: 'test',
                choices: [
                    {
                        uuid: 'test-choice',
                    },
                ],
                mapping: [
                    {
                        uuid: 'new-mapping-unset-destination',
                        sourceId: 'new-source-id',
                        destinationUuid: null,
                    },
                ],
            },
        ],
        expected: [
            {
                ...fixturePreMapping[0],
                choices: [
                    {
                        uuid: 'mr',
                        description: 'mr',
                        extensions: [],
                    },
                    {
                        uuid: 'test-choice',
                    },
                ],
            },
            {
                choices: [
                    { uuid: 'test-choice' },
                ],
                entity: 'test',
                mapping: [
                    {
                        uuid: 'new-mapping-unset-destination',
                        sourceId: 'new-source-id',
                        destinationUuid: null,
                        id: 'test-new-source-id',
                    },
                ],
            },
        ],
    },
    {
        name: 'should not override existing mapping with set destinationUuid',
        initialPremapping: [
            {
                entity: 'test-entity',
                choices: [
                    { uuid: 'choice-1' },
                ],
                mapping: [
                    {
                        uuid: 'existing-mapping',
                        sourceId: 'source-1',
                        destinationUuid: 'already-set-destination',
                    },
                ],
            },
        ],
        premapping: [
            {
                entity: 'test-entity',
                choices: [
                    { uuid: 'choice-1' },
                ],
                mapping: [
                    {
                        uuid: 'existing-mapping',
                        sourceId: 'source-1',
                        destinationUuid: 'new-destination',
                    },
                ],
            },
        ],
        expected: [
            {
                entity: 'test-entity',
                choices: [
                    { uuid: 'choice-1' },
                ],
                mapping: [
                    {
                        uuid: 'existing-mapping',
                        sourceId: 'source-1',
                        destinationUuid: 'already-set-destination',
                    },
                ],
            },
        ],
    },
];

const INIT_STORE_TESTS = [
    {
        name: 'init store',
        connectionId: null,
        force: false,
    },
    {
        name: 'force full reload',
        connectionId: null,
        force: true,
    },
    {
        name: 'full reload cause connectionId change',
        connectionId: 'test-connection-id',
        force: false,
    },
];

const TEST_ENVIRONMENT_INFORMATION_TESTS = [
    {
        name: 'connection set',
        connectionId: 'test-connection-id',
        expected: {
            test: 'value',
        },
        environment: {
            test: 'value',
        },
    },
    {
        name: 'no connection set',
        connectionId: null,
        environment: {
            test: 'value',
        },
        expected: {},
    },
];

const TEST_DATA_SELECTION_TESTS = [
    {
        name: 'connection set',
        connectionId: 'test-connection-id',
        selectionIds: [
            {
                id: 'test-id',
                requiredSelection: true,
            },
        ],
        expected: ['test-id'],
    },
    {
        name: 'no connection set',
        connectionId: null,
        selectionIds: [
            {
                id: 'test-id',
                requiredSelection: true,
            },
        ],
        expected: [],
    },
];

const testCases = {
    premappingValidation: PREMAPPING_VALIDATION_TESTS,
    currencyMismatch: CURRENCY_MISMATCH_TESTS,
    languageMismatch: LANGUAGE_MISMATCH_TESTS,
    migrationAllowed: MIGRATION_ALLOWED_TESTS,
    continueAllowed: CONTINUE_ALLOWED_TESTS,
    setPremapping: SET_PREMAPPING_TESTS,
    initStore: INIT_STORE_TESTS,
    fetchEnvironmentInformation: TEST_ENVIRONMENT_INFORMATION_TESTS,
    fetchDataSelectionIds: TEST_DATA_SELECTION_TESTS,
};

const aclCanMock = jest.fn(() => true);

const repositoryMock = {
    search: jest.fn(() => null),
};

const migrationApiServiceMock = {
    checkConnection: jest.fn(() => Promise.resolve({})),
    getDataSelection: jest.fn(() => Promise.resolve([])),
};

const originalShopware = Shopware;

describe('src/module/swag-migration/store/migration.store', () => {
    let store = null;

    beforeAll(() => {
        Shopware = {
            ...originalShopware,
            Service: () => {
                return {
                    can: aclCanMock,
                };
            },
            Snippet: {
                te: () => true,
                tc: (key) => key,
            },
            Context: {
                api: {
                    languageId: 'test-language-id',
                    apiPath: 'test-api-path',
                },
            },
        };
    });

    beforeEach(async () => {
        jest.resetAllMocks();

        migrationApiServiceMock.checkConnection.mockResolvedValue({});
        migrationApiServiceMock.getDataSelection.mockResolvedValue([]);

        await import('SwagMigrationAssistant/module/swag-migration/store/migration.store');

        store = originalShopware.Store.get('swagMigration');
        store.$reset();

        Shopware.Store.get('notification').$reset();
    });

    afterAll(() => {
        Shopware = originalShopware;
    });

    describe('getters', () => {
        it.each(testCases.premappingValidation)('should validate premapping: $name', async ({ expected, premapping }) => {
            store.premapping = premapping;

            expect(store.isPremappingValid).toBe(expected);
        });

        it.each(testCases.currencyMismatch)(
            'should check currency mismatch: $name',
            async ({ expected, environmentInformation }) => {
                store.environmentInformation = environmentInformation;

                expect(store.hasCurrencyMismatch).toBe(expected);
            },
        );

        it.each(testCases.languageMismatch)(
            'should check language mismatch: $name',
            async ({ expected, environmentInformation }) => {
                store.environmentInformation = environmentInformation;

                expect(store.hasLanguageMismatch).toBe(expected);
            },
        );

        it.each(testCases.migrationAllowed)(
            'should check if migration is allowed: $name',
            async ({ expected, environmentInformation, warningConfirmed, isLoading }) => {
                aclCanMock.mockReturnValueOnce(true);

                Object.keys(defaultStoreData).forEach((key) => {
                    store[key] = defaultStoreData[key];
                });

                store.environmentInformation = environmentInformation;
                store.warningConfirmed = warningConfirmed;
                store.isLoading = isLoading;

                expect(store.isMigrationAllowed).toBe(expected);
            },
        );

        it.each(testCases.continueAllowed)(
            'should check if continue is allowed & set disabled message: $name',
            async ({ expected, disabledMessage, aclRight, storeData }) => {
                aclCanMock.mockReturnValueOnce(aclRight);

                Object.keys(defaultStoreData).forEach((key) => {
                    store[key] = defaultStoreData[key];
                });

                Object.keys(storeData).forEach((key) => {
                    store[key] = storeData[key];
                });

                expect(store.migrationDisabledMessage).toBe(disabledMessage);
                expect(store.isContinueAllowed).toBe(expected);

                aclCanMock.mockClear();
            },
        );
    });

    describe('actions', () => {
        it('should set connection id', async () => {
            const mockValue = 'test-id';

            expect(store.connectionId).toBeNull();
            store.setConnectionId(mockValue);
            expect(store.connectionId).toBe(mockValue);
        });

        it('should set environment information', async () => {
            const mockValue = {
                test: 'value',
            };

            expect(store.environmentInformation).toStrictEqual({});
            store.setEnvironmentInformation(mockValue);
            expect(store.environmentInformation).toStrictEqual(mockValue);
        });

        it('should set last connection check', async () => {
            const mockValue = new Date('2024-01-01T12:00:00Z');

            expect(store.lastConnectionCheck).toBeNull();
            store.setLastConnectionCheck(mockValue);
            expect(store.lastConnectionCheck).toBe(mockValue);
        });

        it('should set is loading', async () => {
            expect(store.isLoading).toBe(false);
            store.setIsLoading(true);
            expect(store.isLoading).toBe(true);
        });

        it('should set latest run', async () => {
            const mockValue = {
                id: 'latest-run-id',
            };

            expect(store.latestRun).toBeNull();
            store.setLatestRun(mockValue);
            expect(store.latestRun).toStrictEqual(mockValue);
        });

        it('should set current connection', async () => {
            const mockValue = {
                id: 'connection-id',
            };

            expect(store.currentConnection).toBeNull();
            store.setCurrentConnection(mockValue);
            expect(store.currentConnection).toStrictEqual(mockValue);
        });

        it('should set is resetting checksums', async () => {
            expect(store.isResettingChecksum).toBe(false);
            store.setIsResettingChecksum(true);
            expect(store.isResettingChecksum).toBe(true);
        });

        it('should set is truncating migration', async () => {
            expect(store.isTruncatingMigration).toBe(false);
            store.setIsTruncatingMigration(true);
            expect(store.isTruncatingMigration).toBe(true);
        });

        it('should set data selection ids', async () => {
            const mockValue = [
                'selection-id-1',
            ];

            expect(store.dataSelectionIds).toStrictEqual([]);
            store.setDataSelectionIds(mockValue);
            expect(store.dataSelectionIds).toStrictEqual(mockValue);
        });

        it('should set data selection table data', async () => {
            const mockValue = [
                {
                    id: 'selection-id-1',
                },
            ];

            expect(store.dataSelectionTableData).toStrictEqual([]);
            store.setDataSelectionTableData(mockValue);
            expect(store.dataSelectionTableData).toStrictEqual(mockValue);
        });

        it('should set is warning confirmed', async () => {
            expect(store.warningConfirmed).toBe(false);
            store.setWarningConfirmed(true);
            expect(store.warningConfirmed).toBe(true);
        });

        it.each(testCases.setPremapping)(
            'should set & merge premapping: $name',
            async ({ expected, initialPremapping, premapping }) => {
                store.premapping = [...initialPremapping];
                store.setPremapping(premapping);

                expect(store.premapping).toStrictEqual(expected);
            },
        );

        it.each(testCases.initStore)('should initialize the store: $name', async ({ connectionId, force }) => {
            const initialStore = {
                isLoading: false,
                latestRun: { id: 'initial-latest-run' },
                currentConnection: { id: 'initial-connection' },
                connectionId: 'initial-connection-id',
                warningConfirmed: true,
                dataSelectionIds: ['initial-selection'],
                lastConnectionCheck: new Date('2024-01-01T12:00:00Z'),
                dataSelectionTableData: [
                    {
                        id: 'initial-selection',
                    },
                ],
                premapping: [
                    {
                        entity: 'something',
                    },
                ],
            };

            Object.keys(initialStore).forEach((key) => {
                store[key] = initialStore[key];
            });

            expect(store.isLoading).toBe(false);
            expect(store.connectionId).toBe('initial-connection-id');
            expect(store.latestRun).toStrictEqual(initialStore.latestRun);
            expect(store.currentConnection).toStrictEqual(initialStore.currentConnection);
            expect(store.warningConfirmed).toBe(true);
            expect(store.dataSelectionIds).toStrictEqual(initialStore.dataSelectionIds);
            expect(store.lastConnectionCheck).toStrictEqual(initialStore.lastConnectionCheck);
            expect(store.dataSelectionTableData).toStrictEqual(initialStore.dataSelectionTableData);
            expect(store.premapping).toStrictEqual(initialStore.premapping);

            repositoryMock.search.mockResolvedValueOnce({
                length: connectionId ? 1 : 0,
                first: () => ({
                    selectedConnectionId: connectionId,
                }),
            });

            const initPromise = store.init(migrationApiServiceMock, repositoryMock, force);
            expect(store.isLoading).toBe(true);

            await initPromise;

            expect(store.isLoading).toBe(false);

            const fullReload = force || !!connectionId;

            expect(store.latestRun).toStrictEqual(fullReload ? null : initialStore.latestRun);
            expect(store.currentConnection).toStrictEqual(fullReload ? null : initialStore.currentConnection);
            expect(store.warningConfirmed).toBe(!fullReload);
            expect(store.dataSelectionIds).toStrictEqual(fullReload ? [] : initialStore.dataSelectionIds);
            expect(store.dataSelectionTableData).toStrictEqual(fullReload ? [] : initialStore.dataSelectionTableData);
            expect(store.premapping).toStrictEqual(fullReload ? [] : initialStore.premapping);

            expect(repositoryMock.search).toHaveBeenCalledTimes(1);
            expect(migrationApiServiceMock.checkConnection).toHaveBeenCalledTimes(1);
            expect(migrationApiServiceMock.getDataSelection).toHaveBeenCalledTimes(fullReload ? 1 : 0);
        });

        it.each(testCases.fetchEnvironmentInformation)(
            'should fetch environment information: $name',
            async ({ expected, environment, connectionId }) => {
                store.connectionId = connectionId;

                migrationApiServiceMock.checkConnection.mockResolvedValueOnce(Promise.resolve(environment));

                expect(store.environmentInformation).toStrictEqual({});
                expect(store.lastConnectionCheck).toBeNull();

                await store.fetchEnvironmentInformation(migrationApiServiceMock);

                expect(migrationApiServiceMock.checkConnection).toHaveBeenCalledTimes(connectionId ? 1 : 0);
                expect(store.environmentInformation).toStrictEqual(expected);

                const notifications = Object.values(Shopware.Store.get('notification').notifications);
                expect(notifications).toHaveLength(0);
            },
        );

        it('should create notification if environment fetch fails without error code defined', async () => {
            store.connectionId = 'test-connection-id';

            migrationApiServiceMock.checkConnection.mockRejectedValueOnce(new Error('fetch failed'));
            expect(store.environmentInformation).toStrictEqual({});

            await store.fetchEnvironmentInformation(migrationApiServiceMock);

            expect(migrationApiServiceMock.checkConnection).toHaveBeenCalledTimes(1);
            expect(store.environmentInformation).toStrictEqual({});

            const notifications = Object.values(Shopware.Store.get('notification').notifications);
            expect(notifications).toHaveLength(1);

            expect(notifications.at(0).message).toBe('swag-migration.api-error.checkConnection');
        });

        it('should create notification if environment fetch fails with error code defined', async () => {
            store.connectionId = 'test-connection-id';

            migrationApiServiceMock.checkConnection.mockRejectedValueOnce({
                response: {
                    data: {
                        errors: [
                            {
                                code: 'CHECK_CONNECTION_FAILED',
                            },
                        ],
                    },
                },
            });
            expect(store.environmentInformation).toStrictEqual({});

            await store.fetchEnvironmentInformation(migrationApiServiceMock);

            expect(migrationApiServiceMock.checkConnection).toHaveBeenCalledTimes(1);
            expect(store.environmentInformation).toStrictEqual({});

            const notifications = Object.values(Shopware.Store.get('notification').notifications);
            expect(notifications).toHaveLength(1);

            expect(notifications.at(0).message).toBe(
                'swag-migration.wizard.pages.credentials.error.CHECK_CONNECTION_FAILED',
            );
        });

        it.each(testCases.fetchDataSelectionIds)(
            'should fetch data selection ids: $name',
            async ({ expected, selectionIds, connectionId }) => {
                store.connectionId = connectionId;

                migrationApiServiceMock.getDataSelection.mockResolvedValueOnce(Promise.resolve(selectionIds));

                expect(store.dataSelectionTableData).toStrictEqual([]);
                expect(store.dataSelectionIds).toStrictEqual([]);

                await store.fetchDataSelectionIds(migrationApiServiceMock);

                expect(migrationApiServiceMock.getDataSelection).toHaveBeenCalledTimes(connectionId ? 1 : 0);
                expect(store.dataSelectionIds).toStrictEqual(expected);

                const notifications = Object.values(Shopware.Store.get('notification').notifications);
                expect(notifications).toHaveLength(0);
            },
        );

        it('should create notification if fetch data selection ids fails', async () => {
            store.connectionId = 'test-connection-id';

            migrationApiServiceMock.getDataSelection.mockRejectedValueOnce(new Error('fetch failed'));
            expect(store.dataSelectionTableData).toStrictEqual([]);
            expect(store.dataSelectionIds).toStrictEqual([]);

            await store.fetchDataSelectionIds(migrationApiServiceMock);

            expect(migrationApiServiceMock.getDataSelection).toHaveBeenCalledTimes(1);
            expect(store.dataSelectionTableData).toStrictEqual([]);
            expect(store.dataSelectionIds).toStrictEqual([]);

            const notifications = Object.values(Shopware.Store.get('notification').notifications);
            expect(notifications).toHaveLength(1);

            expect(notifications.at(0).message).toBe('swag-migration.api-error.getDataSelection');
        });

        it('should return false when connectionId has not changed', async () => {
            store.connectionId = 'existing-connection-id';

            repositoryMock.search.mockResolvedValueOnce({
                length: 1,
                first: () => ({
                    selectedConnectionId: 'existing-connection-id',
                }),
            });

            const result = await store.fetchConnectionId(repositoryMock);

            expect(result).toBe(false);
            expect(store.connectionId).toBe('existing-connection-id');
        });

        it('should handle fetchConnectionId error and create notification', async () => {
            store.connectionId = 'initial-connection-id';

            repositoryMock.search.mockRejectedValueOnce(new Error('fetch failed'));

            const result = await store.fetchConnectionId();

            expect(result).toBe(false);
            expect(store.connectionId).toBeNull();

            const notifications = Object.values(Shopware.Store.get('notification').notifications);
            expect(notifications).toHaveLength(1);

            expect(notifications.at(0).message).toBe('swag-migration.api-error.fetchConnectionId');
        });
    });
});
