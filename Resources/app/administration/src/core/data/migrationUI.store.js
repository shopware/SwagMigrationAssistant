export const UI_COMPONENT_INDEX = Object.freeze({
    WARNING_CONFIRM: -1,
    DATA_SELECTOR: 0,
    PREMAPPING: 1,
    LOADING_SCREEN: 2,
    MEDIA_SCREEN: 3,
    RESULT_SUCCESS: 4,
    PAUSE_SCREEN: 5,
    TAKEOVER: 6,
    CONNECTION_LOST: 7
});

export default {
    namespaced: true,

    state: {
        componentIndex: UI_COMPONENT_INDEX.DATA_SELECTOR,
        isLoading: false,
        isPaused: false,
        isPremappingValid: false,
        dataSelectionIds: [],
        dataSelectionTableData: [],
        premapping: [],
        unfilledPremapping: [],
        filledPremapping: []
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
                    mapping: []
                };

                const newUnfilledGroup = {
                    choices: group.choices,
                    entity: group.entity,
                    mapping: []
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
        }
    },

    getters: {
        isMigrationAllowed(state) {
            const requiredLookup = {};
            state.dataSelectionTableData.forEach((data) => {
                requiredLookup[data.id] = data.requiredSelection;
            });

            return state.dataSelectionIds.some(id => requiredLookup[id] === false);
        }
    }
};
