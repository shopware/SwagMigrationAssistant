import template from './swag-migration-base.html.twig';
import { MIGRATION_STEP } from '../../../../core/service/api/swag-migration.api.service';

const { Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

export interface SwagMigrationBaseData {
    context: unknown;
    storesInitializing: boolean;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Shopware.Component.register('swag-migration-base', {
    template,

    inject: [
        'migrationApiService',
    ],

    data(): SwagMigrationBaseData {
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
        ...mapState(
            () => Store.get('swagMigration'),
            [
                'environmentInformation',
                'connectionId',
                'isLoading',
                'dataSelectionTableData',
                'isMigrationAllowed',
            ],
        ),
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

                if (!response?.step) {
                    return;
                }

                if (response.step !== MIGRATION_STEP.IDLE) {
                    await this.$router.push({ name: 'swag.migration.processScreen' });
                }
            } catch {
                // do nothing
            }
        },

        async initState() {
            const forceFullStateReload = this.$route.query.forceFullStateReload ?? false;
            await Store.get('swagMigration').init(forceFullStateReload);
            this.storesInitializing = false;
        },

        onMigrate() {
            // navigate to process screen
            Store.get('swagMigration').setIsLoading(true);
            this.$router.push({ name: 'swag.migration.processScreen' });
        },
    },
});
