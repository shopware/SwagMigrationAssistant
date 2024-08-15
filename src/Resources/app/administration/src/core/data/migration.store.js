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

        // merges the existing premapping (in the state) with the newly provided one.
        // resets the state premapping if an empty array is passed as an argument.
        setPremapping(state, newPremapping) {
            if (newPremapping === undefined || newPremapping === null || newPremapping.length < 1) {
                state.premapping = [];
                return;
            }

            newPremapping.forEach((group) => {
                // the premapping is grouped by entity, find the corresponding group in the state
                let existingGroup = state.premapping.find(
                    (existingGroupItem) => existingGroupItem.entity === group.entity,
                );

                if (!existingGroup) {
                    // if it doesn't exist, create a new group for this entity with no mappings
                    existingGroup = {
                        choices: group.choices,
                        entity: group.entity,
                        mapping: [],
                    };
                    // and add it to the state premapping groups
                    state.premapping.push(existingGroup);
                } else {
                    // in case the group already exists, override the choices by the latest ones received from the server
                    existingGroup.choices = group.choices;
                }

                group.mapping.forEach((mapping) => {
                    const existingMapping = existingGroup.mapping.find(
                        // sourceId is unique per entity and always provided by the backend
                        (existingMappingItem) => existingMappingItem.sourceId === mapping.sourceId,
                    );

                    if (existingMapping) {
                        // mapping already exist, check if it was already set and override if not
                        if (!existingMapping.destinationUuid) {
                            existingMapping.destinationUuid = mapping.destinationUuid;
                        }
                        return;
                    }

                    const newMapping = {
                        ...mapping,
                        // build a unique identifier, which can be used as a vue key for reactivity (v-for)
                        id: `${existingGroup.entity}-${mapping.sourceId}`,
                    };

                    // either push the new mapping to the start or end
                    // depending on if it is already filled (automatically by the backend)
                    if (mapping.destinationUuid) {
                        existingGroup.mapping.push(newMapping);
                    } else {
                        existingGroup.mapping.unshift(newMapping);
                    }
                });
            });
        },

        setWarningConfirmed(state, confirmed) {
            state.warningConfirmed = confirmed;
        },
    },

    getters: {
        isPremappingValid(state) {
            return !state.premapping.some((group) => {
                return group.mapping.some((mapping) => {
                    return mapping.destinationUuid === null || mapping.destinationUuid === '';
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
                }, { root: true });
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
                }, { root: true });
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
                }, { root: true });
            }
        },
    },
};
