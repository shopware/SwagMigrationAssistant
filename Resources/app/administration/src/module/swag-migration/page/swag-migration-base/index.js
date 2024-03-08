import template from './swag-migration-base.html.twig';
import { MIGRATION_STEP } from '../../../../core/service/api/swag-migration.api.service';

const { Component, State } = Shopware;
const { Criteria } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-base', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationApiService */
        migrationApiService: 'migrationApiService',
        repositoryFactory: 'repositoryFactory',
    },

    data() {
        return {
            context: Shopware.Context.api,
            storesInitializing: true,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        ...mapState('swagMigration/process', [
            'environmentInformation',
            'connectionId',
        ]),

        ...mapState('swagMigration/ui', [
            'isLoading',
            'dataSelectionTableData',
        ]),

        ...mapGetters({
            storeIsMigrationAllowed: 'swagMigration/ui/isMigrationAllowed',
        }),

        isMigrationAllowed() {
            return this.storeIsMigrationAllowed &&
                this.environmentInformation.migrationDisabled === false;
        },

        /**
         * @returns {boolean}
         */
        migrateButtonDisabled() {
            return this.isLoading ||
                !this.isMigrationAllowed;
        },

        migrationGeneralSettingRepository() {
            return this.repositoryFactory.create('swag_migration_general_setting');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            await this.checkMigrationBackendState();
            return this.initState();
        },

        async checkMigrationBackendState() {
            try {
                const response = await this.migrationApiService.getState();
                if (!response || !response.step) {
                    return;
                }

                if (response.step !== MIGRATION_STEP.IDLE) {
                    this.$router.push({ name: 'swag.migration.processScreen' });
                }
            } catch {
                // do nothing
            }
        },

        async initState() {
            await this.initProcessStore();
            await this.initUiStore();
            this.storesInitializing = false;
        },

        async initProcessStore() {
            try {
                State.commit('swagMigration/process/setEnvironmentInformation', {});
                const criteria = new Criteria(1, 1);

                const settings = await this.migrationGeneralSettingRepository.search(criteria, this.context);
                if (settings.length === 0) {
                    return;
                }

                const connectionId = settings.first().selectedConnectionId;
                State.commit('swagMigration/process/setConnectionId', connectionId);

                if (connectionId === null) {
                    return;
                }

                const connectionCheckResponse = await this.migrationApiService.checkConnection(connectionId);
                State.commit('swagMigration/process/setEnvironmentInformation', connectionCheckResponse);
            } catch {
                // do nothing, default state is already set
            }
        },

        async initUiStore() {
            if (this.connectionId === null) {
                return;
            }

            try {
                const dataSelection = await this.migrationApiService.getDataSelection(this.connectionId);
                State.commit('swagMigration/ui/setPremapping', []);
                State.commit('swagMigration/ui/setDataSelectionTableData', dataSelection);
                const selectedIds = dataSelection.filter(selection => selection.requiredSelection)
                    .map(selection => selection.id);
                State.commit('swagMigration/ui/setDataSelectionIds', selectedIds);
            } catch {
                // do nothing, default state is already set
            }
        },

        onMigrate() {
            this.$nextTick(() => {
                // navigate to process screen
                State.commit('swagMigration/ui/setIsLoading', true);
                this.$router.push({ name: 'swag.migration.processScreen' });
            });
        },
    },
});
