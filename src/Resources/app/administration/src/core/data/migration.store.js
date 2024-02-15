const { Criteria } = Shopware.Data;

const migrationApiService = Shopware.Service('migrationApiService');
const repositoryFactory = Shopware.Service('repositoryFactory');

const migrationGeneralSettingRepository = repositoryFactory.create('swag_migration_general_setting');

/**
 * The vuex store for handling all global data that is needed for the migration process.
 * @module
 * @private
 * @package services-settings
 */
export default {
    namespaced: true,

    state: {
        /**
         * The id of the currently selected connection to a source system.
         */
        connectionId: null,

        /**
         * The environment information of the connection check.
         */
        environmentInformation: {},

        /**
         * Date object on when the last connection check request was done.
         */
        lastConnectionCheck: null,

        /**
         * Flag which sets the whole module into a loading state
         */
        isLoading: false,

        /**
         * The possible data that the user can migrate.
         */
        dataSelectionTableData: [],

        /**
         * The selected data ids that the user wants to migrate.
         */
        dataSelectionIds: [],

        /**
         * The premapping structure, that the user must match.
         */
        premapping: [],

        /**
         * Only the unfilled part of the premapping.
         */
        unfilledPremapping: [],

        /**
         * Only the filled part of the premapping.
         */
        filledPremapping: [],

        /**
         * Flag to indicate if the user has confirmed the warning about different currencies and languages.
         * Will also be set to true if there are no warnings.
         */
        warningConfirmed: false,
    },

    mutations: {
        setConnectionId(state, id) {
            state.connectionId = id;
        },

        setEnvironmentInformation(state, environmentInformation) {
            state.environmentInformation = environmentInformation;
        },

        setLastConnectionCheck(state, date) {
            state.lastConnectionCheck = date;
        },

        setIsLoading(state, isLoading) {
            state.isLoading = isLoading;
        },

        setDataSelectionIds(state, newIds) {
            state.dataSelectionIds = newIds;
        },

        setDataSelectionTableData(state, newTableData) {
            state.dataSelectionTableData = newTableData;
        },

        setPremapping(state, newPremapping) {
            if (newPremapping === undefined && newPremapping.length < 1) {
                state.unfilledPremapping = [];
                state.filledPremapping = [];
                return;
            }

            const unfilledMapping = [];
            const filledMapping = [];

            newPremapping.forEach((group) => {
                const newFilledGroup = {
                    choices: group.choices,
                    entity: group.entity,
                    mapping: [],
                };

                const newUnfilledGroup = {
                    choices: group.choices,
                    entity: group.entity,
                    mapping: [],
                };

                group.mapping.forEach((mapping) => {
                    if (mapping.destinationUuid.length > 0) {
                        newFilledGroup.mapping.push(mapping);
                    } else {
                        newUnfilledGroup.mapping.push(mapping);
                    }
                });

                if (newFilledGroup.mapping.length > 0) {
                    filledMapping.push(newFilledGroup);
                }

                if (newUnfilledGroup.mapping.length > 0) {
                    unfilledMapping.push(newUnfilledGroup);
                }
            });

            state.unfilledPremapping = unfilledMapping;
            state.filledPremapping = filledMapping;
            state.premapping = newPremapping;
        },

        setWarningConfirmed(state, confirmed) {
            state.warningConfirmed = confirmed;
        },
    },

    getters: {
        isPremappingValid(state) {
            return state.premapping.length > 0 && !state.premapping.some((group) => {
                return group.mapping.some((mapping) => {
                    return mapping.destinationUuid === null || mapping.destinationUuid.length === 0;
                });
            });
        },

        isMigrationAllowed(state, getters) {
            const tableDataIds = state.dataSelectionTableData.map((data) => {
                if (data.requiredSelection === false) {
                    return data.id;
                }

                return null;
            });

            const migrationAllowedByDataSelection = state.dataSelectionIds.some(id => tableDataIds.includes(id));
            const migrationAllowedByEnvironment = state.environmentInformation?.migrationDisabled === false;

            return migrationAllowedByDataSelection &&
                migrationAllowedByEnvironment &&
                !state.isLoading &&
                getters.isPremappingValid &&
                state.warningConfirmed;
        },
    },

    actions: {
        async init({ commit, dispatch }, forceFullStateReload = false) {
            commit('setIsLoading', true);

            const connectionIdChanged = await dispatch('fetchConnectionId');
            await dispatch('fetchEnvironmentInformation'); // always get the latest environment information

            if (forceFullStateReload || connectionIdChanged) {
                // first clear old user input
                commit('setPremapping', []);
                commit('setDataSelectionIds', []);
                commit('setWarningConfirmed', false);
                // then fetch new data
                await dispatch('fetchDataSelectionIds');
            }

            commit('setIsLoading', false);
        },

        /**
         * @returns {Promise<boolean>} whether the connection id has changed to a new valid one
         */
        async fetchConnectionId({ state, commit, dispatch }) {
            try {
                const criteria = new Criteria(1, 1);
                const settings = await migrationGeneralSettingRepository.search(criteria, Shopware.Context.api);
                if (settings.length === 0) {
                    return false;
                }

                const connectionId = settings.first().selectedConnectionId;
                if (connectionId === state.connectionId) {
                    return false;
                }

                commit('setConnectionId', connectionId);
                return true;
            } catch (e) {
                await dispatch('notification/createNotification', {
                    variant: 'error',
                    title: Shopware.Snippet.tc('global.default.error'),
                    message: Shopware.Snippet.tc('swag-migration.api-error.fetchConnectionId'),
                });
                commit('setConnectionId', null);
                return false;
            }
        },

        async fetchEnvironmentInformation({ state, commit, dispatch }) {
            commit('setEnvironmentInformation', {});
            if (state.connectionId === null) {
                return;
            }

            try {
                const connectionCheckResponse = await migrationApiService.checkConnection(state.connectionId);
                commit('setEnvironmentInformation', connectionCheckResponse);
                commit('setLastConnectionCheck', new Date());
            } catch (e) {
                await dispatch('notification/createNotification', {
                    variant: 'error',
                    title: Shopware.Snippet.tc('global.default.error'),
                    message: Shopware.Snippet.tc('swag-migration.api-error.checkConnection'),
                });
            }
        },

        async fetchDataSelectionIds({ state, commit, dispatch }) {
            commit('setDataSelectionTableData', []);
            if (state.connectionId === null) {
                return;
            }

            try {
                const dataSelection = await migrationApiService.getDataSelection(state.connectionId);
                commit('setDataSelectionTableData', dataSelection);
                const selectedIds = dataSelection.filter(selection => selection.requiredSelection)
                    .map(selection => selection.id);
                commit('setDataSelectionIds', selectedIds);
            } catch (e) {
                await dispatch('notification/createNotification', {
                    variant: 'error',
                    title: Shopware.Snippet.tc('global.default.error'),
                    message: Shopware.Snippet.tc('swag-migration.api-error.getDataSelection'),
                });
            }
        },
    },
};
