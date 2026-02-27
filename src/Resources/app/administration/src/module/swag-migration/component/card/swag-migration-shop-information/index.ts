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

const MIGRATION_POLLING_INTERVAL = 2500 as const;

type PollingType = 'checksum' | 'truncate';

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
    checksumPollingIntervalId: number | null;
    truncatePollingIntervalId: number | null;
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
        'acl',
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        connected: {
            type: Boolean,
            default: false,
        },
    },

    data(): SwagMigrationShopInformationData {
        return {
            migrationStore: Store.get(MIGRATION_STORE_ID),
            checksumPollingIntervalId: null,
            truncatePollingIntervalId: null,
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
                'isTruncatingMigration',
                'connectionId',
                'currentConnection',
                'environmentInformation',
                'lastConnectionCheck',
                'adminLocaleLanguage',
                'adminLocaleRegion',
            ],
        ),

        migrationRunRepository(): TRepository<'swag_migration_run'> {
            return this.repositoryFactory.create('swag_migration_run');
        },

        migrationConnectionRepository(): TRepository<'swag_migration_connection'> {
            return this.repositoryFactory.create('swag_migration_connection');
        },

        migrationGeneralSettingRepository(): TRepository<'swag_migration_general_setting'> {
            return this.repositoryFactory.create('swag_migration_general_setting');
        },

        displayEnvironmentInformation() {
            return this.environmentInformation === null ? {} : this.environmentInformation;
        },

        isUpdating() {
            return this.isResettingChecksum || this.isTruncatingMigration || this.isLoading;
        },

        showUpdateBanner() {
            return this.isResettingChecksum || this.isTruncatingMigration;
        },

        updateBannerTitle() {
            if (this.isResettingChecksum) {
                return this.$tc('swag-migration.index.shopInfoCard.updateBanner.isResettingChecksums.title');
            }

            if (this.isTruncatingMigration) {
                return this.$tc('swag-migration.index.shopInfoCard.updateBanner.isTruncatingMigration.title');
            }

            return '';
        },

        updateBannerMessage() {
            if (this.isResettingChecksum) {
                return this.$tc('swag-migration.index.shopInfoCard.updateBanner.isResettingChecksums.message');
            }

            if (this.isTruncatingMigration) {
                return this.$tc('swag-migration.index.shopInfoCard.updateBanner.isTruncatingMigration.message');
            }

            return '';
        },

        connectionName() {
            return this.connection !== null
                ? this.connection.name
                : this.$tc('swag-migration.index.shopInfoCard.noConnection');
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
            return this.connection !== null;
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
                const [
                    isResettingChecksums,
                    isTruncatingMigration,
                ] = await Promise.all([
                    this.migrationApiService.isResettingChecksums(),
                    this.migrationApiService.isTruncatingMigrationData(),
                    this.updateLastMigrationDate(),
                ]);

                if (isResettingChecksums) {
                    this.registerPolling('checksum');
                }

                if (isTruncatingMigration) {
                    this.registerPolling('truncate');
                }
            } finally {
                this.isLoading = false;
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

        registerPolling(type: PollingType) {
            this.unregisterPolling(type);

            if (type === 'checksum') {
                this.migrationStore.setIsResettingChecksum(true);
                this.checksumPollingIntervalId = setInterval(() => this.poll(type), MIGRATION_POLLING_INTERVAL);
            } else {
                this.migrationStore.setIsTruncatingMigration(true);
                this.truncatePollingIntervalId = setInterval(() => this.poll(type), MIGRATION_POLLING_INTERVAL);
            }
        },

        unregisterPolling(type: PollingType) {
            if (type === 'checksum') {
                if (this.checksumPollingIntervalId) {
                    clearInterval(this.checksumPollingIntervalId);
                }

                this.checksumPollingIntervalId = null;
                this.migrationStore.setIsResettingChecksum(false);
            } else {
                if (this.truncatePollingIntervalId) {
                    clearInterval(this.truncatePollingIntervalId);
                }

                this.migrationStore.setIsTruncatingMigration(false);
                this.truncatePollingIntervalId = null;
            }
        },

        async poll(type: PollingType) {
            const isActive = type === 'checksum' ? this.isResettingChecksum : this.isTruncatingMigration;

            if (!isActive) {
                return;
            }

            try {
                const isStillRunning =
                    type === 'checksum'
                        ? await this.migrationApiService.isResettingChecksums()
                        : await this.migrationApiService.isTruncatingMigrationData();

                if (!isStillRunning) {
                    this.unregisterPolling(type);

                    if (type === 'truncate') {
                        await this.migrationStore.init(
                            this.migrationApiService,
                            this.migrationGeneralSettingRepository,
                            true,
                        );
                    }
                }
            } catch {
                this.unregisterPolling(type);
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc('swag-migration.api-error.getState'),
                });
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

        async onClickRefreshConnection() {
            await this.migrationStore.init(this.migrationApiService, this.migrationGeneralSettingRepository, true);
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
            this.registerPolling('checksum');

            this.showResetChecksumsConfirmModal = false;
            this.confirmModalIsLoading = false;
        },

        async onClickResetMigration() {
            this.confirmModalIsLoading = true;

            await this.migrationApiService.cleanupMigrationData();
            this.registerPolling('truncate');

            this.showResetMigrationConfirmModal = false;
            this.confirmModalIsLoading = false;
        },
    },
});
