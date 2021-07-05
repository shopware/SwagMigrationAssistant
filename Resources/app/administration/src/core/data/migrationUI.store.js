export const UI_COMPONENT_INDEX = Object.freeze({
    WARNING_CONFIRM: -1,
    DATA_SELECTOR: 0,
    PREMAPPING: 1,
    LOADING_SCREEN: 2,
    MEDIA_SCREEN: 3,
    RESULT_SUCCESS: 4,
    PAUSE_SCREEN: 5,
    TAKEOVER: 6,
    CONNECTION_LOST: 7,
});

/**
 * The vuex store for global data handling inside the UI components of the migration module.
 * These are used for preparing and supporting the migration process (which is running in the background)
 * and to display the correct things to the user.
 * @module
 */
export default {
    namespaced: true,

    state: {
        /**
         * The current component to display in the migration process. This is very similar to the migration status,
         * but only represents which component to render at the moment.
         */
        componentIndex: UI_COMPONENT_INDEX.DATA_SELECTOR,

        /**
         * Flag which sets the whole module into a loading state
         */
        isLoading: false,

        /**
         * Flag to set the migration ui into a pause state
         */
        isPaused: false,

        /**
         * Flag to specify that the premapping is valid
         */
        isPremappingValid: false,

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
    },

    mutations: {
        setComponentIndex(state, newIndex) {
            state.componentIndex = newIndex;
        },

        setIsLoading(state, isLoading) {
            state.isLoading = isLoading;
        },

        setIsPaused(state, isPaused) {
            state.isPaused = isPaused;
        },

        setIsPremappingValid(state, isValid) {
            state.isPremappingValid = isValid;
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
    },

    getters: {
        isMigrationAllowed(state) {
            const tableDataIds = state.dataSelectionTableData.map((data) => {
                if (data.requiredSelection === false) {
                    return data.id;
                }

                return null;
            });
            return state.dataSelectionIds.some(id => tableDataIds.includes(id));
        },
    },
};
