import template from './swag-migration-history-detail.html.twig';
import './swag-migration-history-detail.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-history-detail', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationApiService */
        migrationApiService: 'migrationApiService',
        repositoryFactory: 'repositoryFactory',
    },

    data() {
        return {
            runId: '',
            migrationRun: {},
            showModal: true,
            isLoading: true,
            migrationDateOptions: {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            },
            currentTab: 'data',
            context: Shopware.Context.api,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        migrationRunRepository() {
            return this.repositoryFactory.create('swag_migration_run');
        },

        shopFirstLetter() {
            return this.migrationRun.environmentInformation.sourceSystemName === undefined ? 'S' :
                this.migrationRun.environmentInformation.sourceSystemName[0];
        },

        profileIcon() {
            return this.migrationRun.connection === null ||
                this.migrationRun.connection.profile === undefined ||
                this.migrationRun.connection.profile.icon === undefined ? null : this.migrationRun.connection.profile.icon;
        },

        connectionName() {
            return this.migrationRun.connection === null ? '' :
                this.migrationRun.connection.name;
        },

        shopUrl() {
            return this.migrationRun.environmentInformation.sourceSystemDomain === undefined ? '' :
                this.migrationRun.environmentInformation.sourceSystemDomain.replace(/^\s*https?:\/\//, '');
        },

        shopUrlPrefix() {
            if (this.migrationRun.environmentInformation.sourceSystemDomain === undefined) {
                return '';
            }

            const match = this.migrationRun.environmentInformation.sourceSystemDomain.match(/^\s*https?:\/\//);
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

        profileName() {
            return this.migrationRun.connection === null ? '' :
                this.migrationRun.connection.profileName;
        },

        gatewayName() {
            return this.migrationRun.connection === null ? '' :
                this.migrationRun.connection.gatewayName;
        },

        runStatusSnippet() {
            return this.migrationRun.step === null ? '' :
                `swag-migration.history.detailPage.status.${this.migrationRun.step}`;
        },

        runStatusClasses() {
            return this.migrationRun.step === null ? '' :
                `swag-migration-history-detail__run-status-value--${this.migrationRun.step}`;
        },

        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    },

    created() {
        if (!this.$route.params.id) {
            this.isLoading = false;
            this.onCloseModal();
            return Promise.resolve();
        }

        this.runId = this.$route.params.id;
        const criteria = new Criteria(1, 1);
        criteria.addFilter(Criteria.equals('id', this.runId));

        return this.migrationRunRepository.search(criteria, this.context).then((runs) => {
            if (runs.length < 1) {
                this.isLoading = false;
                this.onCloseModal();
                return Promise.resolve();
            }

            this.migrationRun = runs.first();

            return this.migrationApiService.getProfileInformation(
                this.migrationRun.connection.profileName,
                this.migrationRun.connection.gatewayName,
            ).then((profileInformation) => {
                this.migrationRun.connection.profile = profileInformation.profile;

                this.isLoading = false;
                this.$nextTick(() => {
                    this.$refs.tabReference.setActiveItem(this.$refs.dataTabItem);
                });
            });
        }).catch(() => {
            this.isLoading = false;
            this.onCloseModal();
        });
    },

    methods: {
        onCloseModal() {
            this.showModal = false;
            this.$nextTick(() => {
                this.$router.go(-1);
            });
        },

        newActiveTabItem(item) {
            this.currentTab = item.name;
        },
    },
});
