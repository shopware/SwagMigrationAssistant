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
    },

    mutations: {
        setConnectionId(state, id) {
            state.connectionId = id;
        },

        setEnvironmentInformation(state, environmentInformation) {
            state.environmentInformation = environmentInformation;
        },
    },
};
