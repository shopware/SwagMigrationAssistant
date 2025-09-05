import template from './swag-migration-base.html.twig';
import { MIGRATION_API_SERVICE, MIGRATION_STEP } from '../../../../core/service/api/swag-migration.api.service';
import { MIGRATION_STORE_ID } from '../../store/migration.store';

const { Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

/**
 * @private
 */
export interface SwagMigrationBaseData {
    context: unknown;
    storesInitializing: boolean;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
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
            () => Store.get(MIGRATION_STORE_ID),
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
            await Store.get(MIGRATION_STORE_ID).init(forceFullStateReload);
            this.storesInitializing = false;
        },

        onMigrate() {
            // navigate to process screen
            Store.get(MIGRATION_STORE_ID).setIsLoading(true);
            this.$router.push({ name: 'swag.migration.processScreen' });
        },
    },
});
