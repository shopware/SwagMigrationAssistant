import { MIGRATION_STATUS } from '../service/migration/swag-migration-worker-status-manager.service';

export default {
    namespaced: true,

    state: {
        connectionId: null,
        environmentInformation: {},
        runId: null,
        isMigrating: false,
        entityGroups: [],
        currentEntityGroupId: '',
        statusIndex: MIGRATION_STATUS.WAITING
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
        }
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
        }
    }
};
