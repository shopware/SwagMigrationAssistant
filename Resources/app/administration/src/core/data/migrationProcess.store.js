/**
 * @type MIGRATION_STATUS
 */
import { MIGRATION_STATUS } from '../service/migration/swag-migration-worker-status-manager.service';

/**
 * The vuex store for handling all global data that is needed for the migration process.
 * @module
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
         * The id of the current migration run.
         */
        runId: null,

        /**
         * Is the migration currently running.
         */
        isMigrating: false,

        /**
         * The entity groups are generated in the backend depending on the selected data to migrate and contain entities.
         * They are used for the requests to the backend and
         * every entity in every group will be used for the fetch, write and download requests.
         * During a migration their value can change multiple times
         * (e.g. a state change from write to download).
         * In general, they represent the progress and current status of the migration.
         */
        entityGroups: [],

        /**
         * Id of the entity group that is currently being processed.
         */
        currentEntityGroupId: '',

        /**
         * The current status of the migration.
         * Each migration runs through different states (e.g. premapping, fetch, write, download)
         */
        statusIndex: MIGRATION_STATUS.WAITING,
    },

    mutations: {
        setConnectionId(state, id) {
            state.connectionId = id;
        },

        setEnvironmentInformation(state, environmentInformation) {
            state.environmentInformation = environmentInformation;
        },

        setRunId(state, runId) {
            state.runId = runId;
        },

        setIsMigrating(state, isMigrating) {
            state.isMigrating = isMigrating;
        },

        setStatusIndex(state, newIndex) {
            state.statusIndex = newIndex;
        },

        setCurrentEntityGroupId(state, newId) {
            state.currentEntityGroupId = newId;
        },

        setEntityGroups(state, entityGroups) {
            state.entityGroups = entityGroups;
        },

        setEntityProgress(state, { groupId, groupCurrentCount, groupTotal }) {
            const targetGroup = state.entityGroups.find(group => group.id === groupId);

            if (targetGroup === undefined) {
                return;
            }

            if (targetGroup.total !== groupTotal) {
                targetGroup.total = groupTotal;
            }

            targetGroup.currentCount = groupCurrentCount;
            this.state.currentEntityGroupId = targetGroup.id;
        },

        resetProgress(state) {
            state.entityGroups.forEach((data) => {
                data.currentCount = 0;
            });
        },
    },

    getters: {
        displayEntityGroups(state) {
            if (state.statusIndex === MIGRATION_STATUS.PROCESS_MEDIA_FILES) {
                return state.entityGroups.filter((group) => {
                    return group.id === 'processMediaFiles';
                });
            }

            return state.entityGroups.filter((group) => {
                return group.id !== 'processMediaFiles';
            });
        },
    },
};
