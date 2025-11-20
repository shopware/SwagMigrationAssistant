import template from './swag-migration-base.html.twig';
import { MIGRATION_API_SERVICE, MIGRATION_STEP } from '../../../../core/service/api/swag-migration.api.service';
import type { MigrationStore } from '../../store/migration.store';
import { MIGRATION_STORE_ID } from '../../store/migration.store';
import './swag-migration-base.scss';
import type { TRepository } from '../../../../type/types';

const { Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

/**
 * @private
 */
export interface SwagMigrationBaseData {
    migrationStore: MigrationStore;
    warningModalOpen: boolean;
    storesInitializing: boolean;
    context: unknown;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
        'repositoryFactory',
    ],

    data(): SwagMigrationBaseData {
        return {
            migrationStore: Store.get(MIGRATION_STORE_ID),
            warningModalOpen: false,
            storesInitializing: true,
            context: Shopware.Context.api,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        migrationGeneralSettingRepository(): TRepository<'swag_migration_general_setting'> {
            return this.repositoryFactory.create('swag_migration_general_setting');
        },

        warningModalMessage() {
            if (this.hasCurrencyMismatch) {
                return this.$tc('swag-migration.index.warningModal.message.currency');
            }

            if (this.hasLanguageMismatch) {
                return this.$tc('swag-migration.index.warningModal.message.language');
            }

            return '';
        },

        startMigrationButtonTooltip() {
            if (this.migrationDisabledMessage) {
                return {
                    message: this.migrationDisabledMessage,
                    disabled: false,
                };
            }

            return {
                message: '',
                disabled: true,
            };
        },

        buttonTooltip() {
            if (this.isResettingChecksum) {
                return {
                    message: this.$tc('swag-migration.index.shopInfoCard.updateBanner.isResettingChecksums.message'),
                    disabled: false,
                };
            }

            return {
                message: '',
                disabled: true,
            };
        },

        ...mapState(
            () => Store.get(MIGRATION_STORE_ID),
            [
                'isLoading',
                'isResettingChecksum',
                'environmentInformation',
                'connectionId',
                'dataSelectionTableData',
                'migrationDisabledMessage',
                'isMigrationAllowed',
                'isContinueAllowed',
                'warningConfirmed',
                'hasCurrencyMismatch',
                'hasLanguageMismatch',
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

        onWarningModalClose() {
            this.warningModalOpen = false;
        },

        onConfirmWarning() {
            this.migrationStore.setWarningConfirmed(true);
            this.warningModalOpen = false;

            this.onMigrate();
        },

        async checkMigrationBackendState() {
            const response = await this.migrationApiService.getState().catch();

            if (!response?.step) {
                return;
            }

            if (response.step !== MIGRATION_STEP.IDLE) {
                await this.$router.push({
                    name: 'swag.migration.processScreen',
                });
            }
        },

        async initState() {
            const forceFullStateReload = this.$route.query.forceFullStateReload ?? false;
            await this.migrationStore.init(
                this.migrationApiService,
                this.migrationGeneralSettingRepository,
                forceFullStateReload,
            );

            this.storesInitializing = false;
        },

        async onMigrate() {
            if (!this.isMigrationAllowed) {
                this.warningModalOpen = true;
                return;
            }

            this.migrationStore.setWarningConfirmed(false);
            this.migrationStore.setIsLoading(true);
            await this.$router.push({
                name: 'swag.migration.processScreen',
            });
        },
    },
});
