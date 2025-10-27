import type RepositoryType from '@administration/src/core/data/repository.data';
import type {
    TEntity,
    MigrationDataSelection,
    MigrationEnvironmentInformation,
    MigrationPremapping,
} from '../../../type/types';
import type MigrationApiService from '../../../core/service/api/swag-migration.api.service';
import { MIGRATION_API_SERVICE } from '../../../core/service/api/swag-migration.api.service';

const { Criteria } = Shopware.Data;

const migrationApiService: MigrationApiService = Shopware.Service(MIGRATION_API_SERVICE);
const repositoryFactory = Shopware.Service('repositoryFactory');

const migrationGeneralSettingRepository = repositoryFactory.create(
    'swag_migration_general_setting',
) as RepositoryType<'swag_migration_general_setting'>;

/**
 * @private
 */
export const MIGRATION_STORE_ID = 'swagMigration';

type MigrationState = {
    state: {
        isLoading: boolean;
        latestRun: TEntity<'swag_migration_run'> | null;
        currentConnection: TEntity<'swag_migration_connection'> | null;
        isResettingChecksum: boolean;
        warningConfirmed: boolean;
        dataSelectionIds: string[];
        connectionId: string | null;
        lastConnectionCheck: Date | null;
        premapping: MigrationPremapping[];
        dataSelectionTableData: MigrationDataSelection[];
        environmentInformation: MigrationEnvironmentInformation;
    };
    getters: {
        isPremappingValid: () => boolean;
        migrationDisabledMessage: () => string | null;
        isContinueAllowed: () => boolean;
        isMigrationAllowed: () => boolean;
        hasCurrencyMismatch: () => boolean;
        hasLanguageMismatch: () => boolean;
    };
    actions: {
        setLatestRun: (run: TEntity<'swag_migration_run'> | null) => void;
        setCurrentConnection: (connection: TEntity<'swag_migration_connection'> | null) => void;
        setConnectionId: (id: string) => void;
        fetchConnectionId: () => Promise<boolean>;
        setIsLoading: (isLoading: boolean) => void;
        setIsResettingChecksum: (isResetting: boolean) => void;
        fetchDataSelectionIds: () => Promise<void>;
        setLastConnectionCheck: (date: Date) => void;
        setDataSelectionIds: (newIds: string[]) => void;
        fetchEnvironmentInformation: () => Promise<void>;
        setWarningConfirmed: (confirmed: boolean) => void;
        init: (forceFullStateReload?: boolean) => Promise<void>;
        setPremapping: (newPremapping: MigrationPremapping[]) => void;
        createErrorNotification: (errorMessageKey: string) => Promise<void>;
        setDataSelectionTableData: (data: MigrationDataSelection[]) => void;
        setEnvironmentInformation: (environmentInformation: MigrationEnvironmentInformation) => void;
    };
};

/**
 * The pinia store for handling all global data that is needed for the migration process.
 *
 * @private
 * @sw-package fundamentals@after-sales
 */
const migrationStore = Shopware.Store.register({
    id: MIGRATION_STORE_ID,

    state: (): MigrationState['state'] => ({
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
         * Latest migration run object, null if no migration has been started yet.
         */
        latestRun: null,
        /**
         * The possible connection object, null if no connection is selected.
         */
        currentConnection: null,
        /**
         * Flag which sets the checksum is resetting
         */
        isResettingChecksum: false,
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
        isPremappingValid(): boolean {
            return !this.premapping.some((group: MigrationPremapping) => {
                return group.mapping.some((mapping) => {
                    return mapping.destinationUuid === null || mapping.destinationUuid === '';
                });
            });
        },

        hasCurrencyMismatch(): boolean {
            return this.environmentInformation.sourceSystemCurrency !== this.environmentInformation.targetSystemCurrency;
        },

        hasLanguageMismatch(): boolean {
            return this.environmentInformation.sourceSystemLocale !== this.environmentInformation.targetSystemLocale;
        },

        migrationDisabledMessage(): string | null {
            if (!this.dataSelectionTableData.length) {
                return Shopware.Snippet.tc('swag-migration.general.disabledMessages.noData');
            }

            const tableDataIds = this.dataSelectionTableData.map((data: MigrationDataSelection) => {
                if (!data.requiredSelection) {
                    return data.id;
                }

                return null;
            });

            if (this.isResettingChecksum) {
                return Shopware.Snippet.tc('swag-migration.general.disabledMessages.resettingChecksum');
            }

            if (!this.dataSelectionIds.some((id: string) => tableDataIds.includes(id))) {
                return Shopware.Snippet.tc('swag-migration.general.disabledMessages.noSelectedData');
            }

            if (this.environmentInformation?.migrationDisabled !== false) {
                return Shopware.Snippet.tc('swag-migration.general.disabledMessages.disabled');
            }

            if (this.isLoading) {
                return Shopware.Snippet.tc('swag-migration.general.disabledMessages.loading');
            }

            if (!this.isPremappingValid) {
                return Shopware.Snippet.tc('swag-migration.general.disabledMessages.unfilledPremapping');
            }

            return null;
        },

        isContinueAllowed(): boolean {
            return this.migrationDisabledMessage === null;
        },

        isMigrationAllowed(): boolean {
            const hasWarning = this.hasCurrencyMismatch || this.hasLanguageMismatch;

            if (hasWarning && !this.warningConfirmed) {
                return false;
            }

            return this.isContinueAllowed;
        },
    },

    actions: {
        setConnectionId(id: string) {
            this.connectionId = id;
        },

        setEnvironmentInformation(environmentInformation: MigrationEnvironmentInformation) {
            this.environmentInformation = environmentInformation;
        },

        setLastConnectionCheck(date: Date) {
            this.lastConnectionCheck = date;
        },

        setIsLoading(isLoading: boolean) {
            this.isLoading = isLoading;
        },

        setLatestRun(run: TEntity<'swag_migration_run'> | null) {
            this.latestRun = run;
        },

        setCurrentConnection(connection: TEntity<'swag_migration_connection'> | null) {
            this.currentConnection = connection;
        },

        setIsResettingChecksum(isResetting: boolean) {
            this.isResettingChecksum = isResetting;
        },

        setDataSelectionIds(newIds: string[]) {
            this.dataSelectionIds = newIds;
        },

        setDataSelectionTableData(data: MigrationDataSelection[]) {
            this.dataSelectionTableData = data;
        },

        setWarningConfirmed(confirmed: boolean) {
            this.warningConfirmed = confirmed;
        },

        // merges the existing premapping (in the state) with the newly provided one.
        // resets the state premapping if an empty array is passed as an argument.
        setPremapping(newPremapping: MigrationPremapping[]) {
            if (newPremapping === undefined || newPremapping === null || newPremapping.length < 1) {
                this.premapping = [];
                return;
            }

            newPremapping.forEach((group) => {
                // the premapping is grouped by entity, find the corresponding group in the state
                let existingGroup = this.premapping.find(
                    (existingGroupItem: MigrationPremapping) => existingGroupItem.entity === group.entity,
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
                        (existingMappingItem: { sourceId: string }) => {
                            return existingMappingItem.sourceId === mapping.sourceId;
                        },
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

        async init(forceFullStateReload = false) {
            this.isLoading = true;

            const connectionIdChanged = await this.fetchConnectionId();
            // Always fetch latest environment info
            await this.fetchEnvironmentInformation();

            if (forceFullStateReload || connectionIdChanged) {
                // First, clear old user input
                this.latestRun = null;
                this.currentConnection = null;
                this.warningConfirmed = false;
                this.dataSelectionIds = [];
                this.lastConnectionCheck = null;
                this.premapping = [];
                this.dataSelectionTableData = [];

                // Then fetch new data
                await this.fetchDataSelectionIds();
            }

            this.isLoading = false;
        },

        async fetchConnectionId(): Promise<boolean> {
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
            } catch {
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
            } catch {
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
                this.dataSelectionIds = dataSelection
                    .filter((selection: MigrationDataSelection) => selection.requiredSelection)
                    .map((selection: MigrationDataSelection) => selection.id);
            } catch {
                await this.createErrorNotification('swag-migration.api-error.getDataSelection');
            }
        },

        async createErrorNotification(errorMessageKey: string) {
            await this.$patch(() => {
                // Assuming a notification system exists.
                // Replace this with how notifications are handled in your system.
                Shopware.State.dispatch('notification/createNotification', {
                    variant: 'error',
                    title: Shopware.Snippet.tc('global.default.error'),
                    message: Shopware.Snippet.tc(errorMessageKey),
                });
            });
        },
    },
});

/**
 * @private
 */
export type MigrationStore = ReturnType<typeof migrationStore>;

/**
 * @private
 */
export default migrationStore;
