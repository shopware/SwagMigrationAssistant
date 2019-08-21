import template from './swag-migration-history-detail.html.twig';
import './swag-migration-history-detail.scss';

const { Component, State } = Shopware;
const CriteriaFactory = Shopware.DataDeprecated.CriteriaFactory;

Component.register('swag-migration-history-detail', {
    template,

    data() {
        return {
            runId: '',
            migrationRun: {},
            showModal: true,
            isLoading: true,
            migrationDateOptions: {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            },
            currentTab: 'data'
        };
    },

    computed: {
        migrationRunStore() {
            return State.getStore('swag_migration_run');
        },

        shopFirstLetter() {
            return this.migrationRun.environmentInformation.sourceSystemName === undefined ? 'S' :
                this.migrationRun.environmentInformation.sourceSystemName[0];
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
            return this.migrationRun.status === null ? '' :
                `swag-migration.history.detailPage.status.${this.migrationRun.status}`;
        },

        runStatusClasses() {
            return this.migrationRun.status === null ? '' :
                `swag-migration-history-detail__run-status-value--${this.migrationRun.status}`;
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    created() {
        if (!this.$route.params.id) {
            this.isLoading = false;
            this.onCloseModal();
            return;
        }

        this.runId = this.$route.params.id;
        const params = {
            limit: 1,
            criteria: CriteriaFactory.equals('id', this.runId)
        };

        this.migrationRunStore.getList(params).then((response) => {
            if (!response ||
                (response && response.items.length < 1)) {
                this.isLoading = false;
                this.onCloseModal();
                return;
            }

            this.migrationRun = response.items[0];
            this.isLoading = false;
            this.$nextTick(() => {
                this.$refs.tabReference.setActiveItem(this.$refs.dataTabItem);
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
        }
    }
});
