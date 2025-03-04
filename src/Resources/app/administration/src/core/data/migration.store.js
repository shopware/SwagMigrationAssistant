const { Criteria } = Shopware.Data;

const migrationApiService = Shopware.Service('migrationApiService');
const repositoryFactory = Shopware.Service('repositoryFactory');

const migrationGeneralSettingRepository = repositoryFactory.create('swag_migration_general_setting');

/**
 * The pinia store for handling all global data that is needed for the migration process.
 * @module
 * @private
 * @sw-package fundamentals@after-sales
 */
export default {
    namespaced: true,

    state: () => ({
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
    }),

    getters: {
        isPremappingValid(state) {
            return !state.premapping.some((group) => {
                return group.mapping.some((mapping) => {
                    return mapping.destinationUuid === null || mapping.destinationUuid === '';
                });
            });
        },

        isMigrationAllowed(state) {
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
                state.isPremappingValid &&
                state.warningConfirmed;
        },
    },

    actions: {
        setConnectionId(id) {
            this.connectionId = id;
        },

        setEnvironmentInformation(environmentInformation) {
            this.environmentInformation = environmentInformation;
        },

        setLastConnectionCheck(date) {
            this.lastConnectionCheck = date;
        },

        setIsLoading(isLoading) {
            this.isLoading = isLoading;
        },

        setDataSelectionIds(newIds) {
            this.dataSelectionIds = newIds;
        },

        setDataSelectionTableData(newTableData) {
            this.dataSelectionTableData = newTableData;
        },

        // merges the existing premapping (in the state) with the newly provided one.
        // resets the state premapping if an empty array is passed as an argument.
        setPremapping(newPremapping) {
            if (newPremapping === undefined || newPremapping === null || newPremapping.length < 1) {
                this.premapping = [];
                return;
            }

            newPremapping.forEach((group) => {
                // the premapping is grouped by entity, find the corresponding group in the state
                let existingGroup = this.premapping.find(
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
                    this.premapping.push(existingGroup);
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

        setWarningConfirmed(confirmed) {
            this.warningConfirmed = confirmed;
        },

        async init(forceFullStateReload = false) {
            this.isLoading = true;

            const connectionIdChanged = await this.fetchConnectionId();
            await this.fetchEnvironmentInformation(); // Always fetch latest environment info

            if (forceFullStateReload || connectionIdChanged) {
                // First, clear old user input
                this.premapping = [];
                this.dataSelectionIds = [];
                this.warningConfirmed = false;

                // Then fetch new data
                await this.fetchDataSelectionIds();
            }

            this.isLoading = false;
        },

        /**
         * @returns {Promise<boolean>} whether the connection id has changed to a new valid one
         */
        async fetchConnectionId() {
            try {
                const criteria = new Criteria(1, 1);
                const settings = await migrationGeneralSettingRepository.search(criteria, Shopware.Context.api);

                if (settings.length === 0) {
                    return false;
                }

                const newConnectionId = settings.first().selectedConnectionId;
                if (newConnectionId === this.connectionId) {
                    return false;
                }

                this.connectionId = newConnectionId;
                return true;
            } catch (e) {
                await this.createErrorNotification('swag-migration.api-error.fetchConnectionId');
                this.connectionId = null;
                return false;
            }
        },

        async fetchEnvironmentInformation() {
            this.environmentInformation = {};

            if (this.connectionId === null) {
                return;
            }

            try {
                this.environmentInformation = await migrationApiService.checkConnection(this.connectionId);
                this.lastConnectionCheck = new Date();
            } catch (e) {
                await this.createErrorNotification('swag-migration.api-error.checkConnection');
            }
        },

        async fetchDataSelectionIds() {
            this.dataSelectionTableData = [];

            if (this.connectionId === null) {
                return;
            }

            try {
                const dataSelection = await migrationApiService.getDataSelection(this.connectionId);
                this.dataSelectionTableData = dataSelection;
                this.dataSelectionIds = dataSelection.filter(selection => selection.requiredSelection)
                    .map(selection => selection.id);
            } catch (e) {
                await this.createErrorNotification('swag-migration.api-error.getDataSelection');
            }
        },

        async createErrorNotification(errorMessageKey) {
            await this.$patch(() => {
                // Assuming notification system exists
                // Replace this with how notifications are handled in your system
                Shopware.State.dispatch('notification/createNotification', {
                    variant: 'error',
                    title: Shopware.Snippet.tc('global.default.error'),
                    message: Shopware.Snippet.tc(errorMessageKey),
                });
            });
        },
    },
};
