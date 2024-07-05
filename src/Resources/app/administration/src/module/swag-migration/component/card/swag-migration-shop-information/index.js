import template from './swag-migration-shop-information.html.twig';
import './swag-migration-shop-information.scss';

const { Component, Mixin, State } = Shopware;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();
const { format } = Shopware.Utils;
const { Criteria } = Shopware.Data;

const BADGE_TYPE = Object.freeze({
    SUCCESS: 'success',
    DANGER: 'danger',
});

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-shop-information', {
    template,
    inject: {
        /** @var {MigrationApiService} migrationApiService */
        migrationApiService: 'migrationApiService',
        repositoryFactory: 'repositoryFactory',
    },

    mixins: [
        Mixin.getByName('notification'),
    ],

    filters: {
        localizedNumberFormat(value) {
            const locale = `${this.adminLocaleLanguage}-${this.adminLocaleRegion}`;
            const formatter = new Intl.NumberFormat(locale);
            return formatter.format(value);
        },
    },

    props: {
        connected: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            confirmModalIsLoading: false,
            showRemoveCredentialsConfirmModal: false,
            showResetChecksumsConfirmModal: false,
            showResetMigrationConfirmModal: false,
            lastMigrationDate: '-',
            connection: null,
            context: Shopware.Context.api,
        };
    },

    computed: {
        ...mapState('swagMigration', [
            'connectionId',
            'environmentInformation',
            'lastConnectionCheck',
        ]),

        ...mapGetters([
            'adminLocaleLanguage',
            'adminLocaleRegion',
        ]),

        displayEnvironmentInformation() {
            return this.environmentInformation === null ? {} :
                this.environmentInformation;
        },

        migrationRunRepository() {
            return this.repositoryFactory.create('swag_migration_run');
        },

        migrationConnectionRepository() {
            return this.repositoryFactory.create('swag_migration_connection');
        },

        connectionName() {
            return this.connection !== null ?
                this.connection.name :
                this.$tc('swag-migration.index.shopInfoCard.noConnection');
        },

        shopUrl() {
            return this.displayEnvironmentInformation.sourceSystemDomain === undefined ? '' :
                this.displayEnvironmentInformation.sourceSystemDomain.replace(/^\s*https?:\/\//, '');
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
            return (this.shopUrlPrefix === 'https://');
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
            return this.displayEnvironmentInformation.sourceSystemName === undefined ? 'S' :
                this.displayEnvironmentInformation.sourceSystemName[0];
        },

        profile() {
            return this.connection === null || this.connection.profile === undefined ? '' :
                // eslint-disable-next-line max-len
                `${this.connection.profile.sourceSystemName} ${this.connection.profile.version} - ${this.connection.profile.author}`;
        },

        profileIcon() {
            return this.connection === null ||
                this.connection.profile === undefined ||
                this.connection.profile.icon === undefined ? null : this.connection.profile.icon;
        },

        gateway() {
            return this.connection === null || this.connection.gateway === undefined ? '' :
                this.connection.gateway.snippet;
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
            /**
             * @param {string} newConnectionId
             */
            handler(newConnectionId) {
                this.fetchConnection(newConnectionId);
            },
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.updateLastMigrationDate();
        },

        openResetMigrationModal() {
            this.showResetMigrationConfirmModal = true;
            this.$router.push({
                name: 'swag.migration.index.resetMigration',
            });
        },

        onCloseResetModal() {
            this.showResetMigrationConfirmModal = false;
            this.$router.push({
                name: 'swag.migration.index.main',
            });
        },

        updateLastMigrationDate() {
            const criteria = new Criteria(1, 1);
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            return this.migrationRunRepository.search(criteria, this.context).then((runs) => {
                if (runs.length > 0) {
                    this.lastMigrationDate = runs.first().createdAt;
                } else {
                    this.lastMigrationDate = '-';
                }
            });
        },

        /**
         * @param {string} connectionId
         */
        fetchConnection(connectionId) {
            if (!connectionId) {
                return Promise.resolve();
            }

            return this.migrationConnectionRepository.get(connectionId, this.context)
                .then((connection) => {
                    if (!connection) {
                        return Promise.resolve(null);
                    }
                    delete connection.credentialFields;
                    this.connection = connection;

                    return this.migrationApiService.getProfileInformation(
                        connection.profileName,
                        connection.gatewayName,
                    );
                }).then((profileInformation) => {
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

        onClickRemoveConnectionCredentials() {
            this.confirmModalIsLoading = true;
            return this.migrationApiService.updateConnectionCredentials(
                this.connectionId,
                { },
            ).then(() => {
                this.$router.go(); // Refresh the page
            });
        },

        onClickResetChecksums() {
            this.confirmModalIsLoading = true;
            return this.migrationApiService.resetChecksums(this.connectionId).then(() => {
                this.showResetChecksumsConfirmModal = false;
                this.confirmModalIsLoading = false;
            });
        },

        onClickResetMigration() {
            this.confirmModalIsLoading = true;
            return this.migrationApiService.cleanupMigrationData().then(() => {
                this.showResetMigrationConfirmModal = false;
                this.confirmModalIsLoading = false;

                this.$nextTick(() => {
                    this.$router.go(); // reload page
                });
            }).catch(() => {
                this.showResetMigrationConfirmModal = false;
                this.confirmModalIsLoading = false;

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
            });
        },

        onClickRefreshConnection() {
            return State.dispatch('swagMigration/init', true);
        },
    },
});
