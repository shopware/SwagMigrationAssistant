import template from './swag-migration-shop-information.html.twig';
import './swag-migration-shop-information.scss';
import type {
    MigrationConnection,
    MigrationProfile,
    TEntity,
    TEntityCollection,
    TRepository,
} from '../../../../../type/types';
import { MIGRATION_API_SERVICE } from '../../../../../core/service/api/swag-migration.api.service';
import { MIGRATION_STORE_ID, type MigrationStore } from '../../../store/migration.store';

const { Mixin, Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();
const { format } = Shopware.Utils;
const { Criteria } = Shopware.Data;

/**
 * @private
 */
export const BADGE_TYPE = {
    SUCCESS: 'success',
    DANGER: 'danger',
} as const;

const MIGRATION_RESET_CHECKSUM_POLLING_INTERVAL = 2500 as const;

/**
 * @private
 */
export interface SwagMigrationShopInformationData {
    migrationStore: MigrationStore;
    confirmModalIsLoading: boolean;
    showRemoveCredentialsConfirmModal: boolean;
    showResetChecksumsConfirmModal: boolean;
    showResetMigrationConfirmModal: boolean;
    lastMigrationDate: string;
    connection: MigrationConnection | null;
    context: unknown;
    pollingIntervalId: number | null;
    isLoading: boolean;
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

    mixins: [
        Mixin.getByName('notification'),
    ],

    filters: {
        localizedNumberFormat(value: number) {
            const locale = `${this.adminLocaleLanguage}-${this.adminLocaleRegion}`;

            return Intl.NumberFormat(locale).format(value);
        },
    },

    props: {
        connected: {
            type: Boolean,
            default: false,
        },
    },

    data(): SwagMigrationShopInformationData {
        return {
            migrationStore: Store.get(MIGRATION_STORE_ID),
            pollingIntervalId: null,
            confirmModalIsLoading: false,
            showRemoveCredentialsConfirmModal: false,
            showResetChecksumsConfirmModal: false,
            showResetMigrationConfirmModal: false,
            lastMigrationDate: '-',
            connection: null,
            context: Shopware.Context.api,
            isLoading: false,
        };
    },

    computed: {
        ...mapState(
            () => Store.get(MIGRATION_STORE_ID),
            [
                'isResettingChecksum',
                'connectionId',
                'currentConnection',
                'environmentInformation',
                'lastConnectionCheck',
                'adminLocaleLanguage',
                'adminLocaleRegion',
            ],
        ),

        displayEnvironmentInformation() {
            return this.environmentInformation === null ? {} : this.environmentInformation;
        },

        migrationRunRepository(): TRepository<'swag_migration_run'> {
            return this.repositoryFactory.create('swag_migration_run');
        },

        migrationConnectionRepository(): TRepository<'swag_migration_connection'> {
            return this.repositoryFactory.create('swag_migration_connection');
        },

        connectionName() {
            return this.connection !== null
                ? this.connection?.name
                : this.$tc('swag-migration.index.shopInfoCard.noConnection');
        },

        shopUrl() {
            return this.displayEnvironmentInformation.sourceSystemDomain === undefined
                ? ''
                : this.displayEnvironmentInformation.sourceSystemDomain.replace(/^\s*https?:\/\//, '');
        },

        shopUrlPrefix() {
            if (this.displayEnvironmentInformation.sourceSystemDomain === undefined) {
                return '';
            }

            const match = this.displayEnvironmentInformation.sourceSystemDomain.match(/^\s*https?:\/\//);

            if (match === null) {
                return '';
            }

            return match[0];
        },

        sslActive() {
            return this.shopUrlPrefix === 'https://';
        },

        shopUrlPrefixClass() {
            return this.sslActive ? 'swag-migration-shop-information__shop-domain-prefix--is-ssl' : '';
        },

        connectionBadgeLabel() {
            if (this.serverUnreachable) {
                return 'swag-migration.index.shopInfoCard.serverUnreachable';
            }

            if (this.connected) {
                return 'swag-migration.index.shopInfoCard.connected';
            }

            return 'swag-migration.index.shopInfoCard.notConnected';
        },

        connectionBadgeVariant() {
            if (this.connected) {
                return BADGE_TYPE.SUCCESS;
            }

            return BADGE_TYPE.DANGER;
        },

        shopFirstLetter() {
            return this.displayEnvironmentInformation.sourceSystemName?.charAt(0) ?? 'S';
        },

        profile() {
            return this.connection === null || this.connection.profile === undefined
                ? ''
                : // eslint-disable-next-line max-len
                  `${this.connection.profile.sourceSystemName} ${this.connection.profile.version} - ${this.connection.profile.author}`;
        },

        profileIcon() {
            return this.connection === null ||
                this.connection.profile === undefined ||
                this.connection.profile.icon === undefined
                ? null
                : this.connection.profile.icon;
        },

        gateway() {
            return this.connection === null || this.connection.gateway === undefined ? '' : this.connection.gateway.snippet;
        },

        formattedLastConnectionCheckDate() {
            return format.date(this.lastConnectionCheck);
        },

        formattedLastMigrationDateTime() {
            return format.date(this.lastMigrationDate);
        },

        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        showMoreInformation() {
            return this.connection !== null && this.connection !== undefined;
        },
    },

    watch: {
        $route: {
            immediate: true,
            handler() {
                this.showResetMigrationConfirmModal = this.$route.meta.resetMigration;
            },
        },

        connectionId: {
            immediate: true,
            handler(newConnectionId: string) {
                this.fetchConnection(newConnectionId);
            },
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.isLoading = true;

            try {
                const [isResetting] = await Promise.all([
                    this.migrationApiService.isResettingChecksums(),
                    this.updateLastMigrationDate(),
                ]);

                if (isResetting) {
                    this.registerPolling();
                }
            } finally {
                this.isLoading = false;
            }
        },

        openResetMigrationModal() {
            this.showResetMigrationConfirmModal = true;
        },

        async onCloseResetModal() {
            this.showResetMigrationConfirmModal = false;
            await this.$router.push({
                name: 'swag.migration.index.main',
            });
        },

        unregisterPolling() {
            if (this.pollingIntervalId) {
                clearInterval(this.pollingIntervalId);
            }

            this.migrationStore.setIsResettingChecksum(false);
            this.pollingIntervalId = null;
        },

        registerPolling() {
            this.unregisterPolling();

            this.migrationStore.setIsResettingChecksum(true);
            this.pollingIntervalId = setInterval(this.resetCheckSumPoller, MIGRATION_RESET_CHECKSUM_POLLING_INTERVAL);
        },

        async resetCheckSumPoller() {
            if (!this.isResettingChecksum) {
                return;
            }

            try {
                if ((await this.migrationApiService.isResettingChecksums()) === false) {
                    this.unregisterPolling();
                }
            } catch {
                this.unregisterPolling();
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.getState'),
                });
            }
        },

        async updateLastMigrationDate() {
            const storedRun = Shopware.Store.get(MIGRATION_STORE_ID).latestRun;

            if (storedRun) {
                this.lastMigrationDate = storedRun.createdAt;
                return Promise.resolve();
            }

            const criteria = new Criteria(1, 1).addSorting(Criteria.sort('createdAt', 'DESC'));

            return this.migrationRunRepository
                .search(criteria, this.context)
                .then((runs: TEntityCollection<'swag_migration_run'>) => {
                    if (runs.length > 0) {
                        this.lastMigrationDate = runs.first().createdAt;
                        Shopware.Store.get(MIGRATION_STORE_ID).setLatestRun(runs.first());
                    } else {
                        this.lastMigrationDate = '-';
                    }
                });
        },

        async fetchConnection(connectionId: string | null) {
            if (!connectionId) {
                return Promise.resolve();
            }

            if (this.currentConnection) {
                this.connection = this.currentConnection;
                return Promise.resolve();
            }

            return this.migrationConnectionRepository
                .get(connectionId, this.context)
                .then((connection: TEntity<'swag_migration_connection'>) => {
                    if (!connection) {
                        return Promise.resolve(null);
                    }

                    delete connection.credentialFields;
                    this.connection = connection;
                    Shopware.Store.get(MIGRATION_STORE_ID).setCurrentConnection(connection);

                    return this.migrationApiService.getProfileInformation(connection.profileName, connection.gatewayName);
                })
                .then((profileInformation: MigrationProfile) => {
                    if (!profileInformation) {
                        return;
                    }

                    this.connection.profile = profileInformation.profile;
                    this.connection.gateway = profileInformation.gateway;
                });
        },

        onClickEditConnectionCredentials() {
            this.$router.push({
                name: 'swag.migration.wizard.credentials',
                params: {
                    connectionId: this.connectionId,
                },
            });
        },

        onClickCreateConnection() {
            this.$router.push({
                name: 'swag.migration.wizard.connectionCreate',
            });
        },

        onClickCreateInitialConnection() {
            this.$router.push({
                name: 'swag.migration.wizard.introduction',
            });
        },

        onClickSelectConnection() {
            this.$router.push({
                name: 'swag.migration.wizard.connectionSelect',
            });
        },

        onClickProfileInstallation() {
            this.$router.push({
                name: 'swag.migration.wizard.profileInstallation',
            });
        },

        async onClickRemoveConnectionCredentials() {
            this.confirmModalIsLoading = true;

            return this.migrationApiService.updateConnectionCredentials(this.connectionId, {}).then(() => {
                this.$router.go();
            });
        },

        async onClickResetChecksums() {
            this.confirmModalIsLoading = true;

            await this.migrationApiService.resetChecksums(this.connectionId);
            this.registerPolling();

            this.showResetChecksumsConfirmModal = false;
            this.confirmModalIsLoading = false;
        },

        async onClickResetMigration() {
            this.confirmModalIsLoading = true;

            return this.migrationApiService
                .cleanupMigrationData()
                .catch(() => {
                    this.createNotificationError({
                        title: this.$t(
                            'swag-migration.index.shopInfoCard.resetMigrationConfirmDialog.errorNotification.title',
                        ),
                        message: this.$t(
                            'swag-migration.index.shopInfoCard.resetMigrationConfirmDialog.errorNotification.message',
                        ),
                        variant: 'error',
                        growl: true,
                    });
                })
                .finally(async () => {
                    this.confirmModalIsLoading = false;
                    await this.onCloseResetModal();
                    window.location.reload();
                });
        },

        onClickRefreshConnection() {
            return this.migrationStore.init(true);
        },
    },
});
