/**
 * @package services-settings
 */

/**
 * The vuex store for global data handling inside the UI components of the migration module.
 * These are used for preparing and supporting the migration process (which is running in the background)
 * and to display the correct things to the user.
 * @module
 * @private
 * @package services-settings
 */
export default {
    namespaced: true,

    state: {
        /**
         * Flag which sets the whole module into a loading state
         */
        isLoading: false,

        /**
         * The selected data ids that the user wants to migrate.
         */
        dataSelectionIds: [],

        /**
         * The possible data that the user can migrate.
         */
        dataSelectionTableData: [],

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
         * Flag to specify that the premapping is valid
         */
        isPremappingValid: false,
    },

    mutations: {
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

        setIsPremappingValid(state, isValid) {
            state.isPremappingValid = isValid;
        },
    },

    getters: {
        isMigrationAllowed(state) {
            const tableDataIds = state.dataSelectionTableData.map((data) => {
                if (data.requiredSelection === false) {
                    return data.id;
                }

                return null;
            });

            return state.dataSelectionIds.some(id => tableDataIds.includes(id)) && state.isPremappingValid;
        },
    },
};
