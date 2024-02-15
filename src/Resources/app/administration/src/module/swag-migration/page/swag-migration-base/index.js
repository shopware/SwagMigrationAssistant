import template from './swag-migration-base.html.twig';
import { MIGRATION_STEP } from '../../../../core/service/api/swag-migration.api.service';

const { Component, State } = Shopware;
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
        ...mapState('swagMigration', [
            'environmentInformation',
            'connectionId',
            'isLoading',
            'dataSelectionTableData',
        ]),

        ...mapGetters({
            isMigrationAllowed: 'swagMigration/isMigrationAllowed',
        }),
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
            const forceFullStateReload = this.$route.query.forceFullStateReload ?? false;
            await State.dispatch('swagMigration/init', forceFullStateReload);
            this.storesInitializing = false;
        },

        onMigrate() {
            // navigate to process screen
            State.commit('swagMigration/setIsLoading', true);
            this.$router.push({ name: 'swag.migration.processScreen' });
        },
    },
});
