import { Component, State } from 'src/core/shopware';
import { format } from 'src/core/service/util.service';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import template from './swag-migration-shop-information.html.twig';
import './swag-migration-shop-information.scss';

const BADGE_TYPE = Object.freeze({
    SUCCESS: 'success',
    DANGER: 'danger'
});

Component.register('swag-migration-shop-information', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService'
    },

    props: {
        connected: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            showMoreInformation: true,
            showConfirmModal: false,
            lastMigrationDate: '-',
            connection: null,
            /** @type ApiService */
            migrationRunStore: State.getStore('swag_migration_run'),
            /** @type ApiService */
            migrationConnectionStore: State.getStore('swag_migration_connection'),
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess'),
            /** @type MigrationUIStore */
            migrationUIStore: State.getStore('migrationUI')
        };
    },

    filters: {
        localizedNumberFormat(value) {
            const locale = State.getStore('adminLocale').locale;
            const formatter = new Intl.NumberFormat(locale);
            return formatter.format(value);
        }
    },

    computed: {
        environmentInformation() {
            if (this.migrationProcessStore.state.environmentInformation !== null) {
                return this.migrationProcessStore.state.environmentInformation;
            }

            return {};
        },

        totalEntityCounts() {
            if (this.environmentInformation.totals !== undefined) {
                return this.environmentInformation.totals;
            }

            return {};
        },

        shopUrl() {
            if (this.environmentInformation.sourceSystemDomain === undefined) {
                return '-';
            }
            return this.environmentInformation.sourceSystemDomain.replace(/^\s*https?:\/\//, '');
        },

        shopUrlPrefix() {
            if (this.environmentInformation.sourceSystemDomain === undefined) {
                return '';
            }

            const match = this.environmentInformation.sourceSystemDomain.match(/^\s*https?:\/\//);
            if (match === null) {
                return '';
            }

            return match[0];
        },

        sslActive() {
            if (this.environmentInformation.sourceSystemDomain === undefined) {
                return false;
            }
            return (this.shopUrlPrefix === 'https://');
        },

        shopUrlPrefixClass() {
            if (this.sslActive) {
                return 'swag-migration-shop-information__shop-domain-prefix--is-ssl';
            }

            return '';
        },

        connectionBadgeLabel() {
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

        shopSystem() {
            if (this.environmentInformation.sourceSystemName === undefined) {
                return '';
            }

            return this.environmentInformation.sourceSystemName;
        },

        shopVersion() {
            if (this.environmentInformation.sourceSystemVersion === undefined) {
                return '';
            }

            return this.environmentInformation.sourceSystemVersion;
        },

        shopFirstLetter() {
            if (this.environmentInformation.sourceSystemName === undefined) {
                return 'S';
            }
            return this.environmentInformation.sourceSystemName[0];
        },

        gateway() {
            if (!this.connection) {
                return '';
            }

            return this.connection.profile.gatewayName;
        },

        lastMigrationDateString() {
            return format.date(this.lastMigrationDate);
        },

        lastMigrationTimeString() {
            return format.date(this.lastMigrationDate, {
                day: undefined,
                month: undefined,
                year: undefined,
                hour: 'numeric',
                minute: '2-digit'
            });
        },

        lastMigrationDateTimeParams() {
            return {
                date: this.lastMigrationDateString,
                time: this.lastMigrationTimeString
            };
        }
    },

    watch: {
        'migrationProcessStore.state.connectionId': {
            immediate: true,
            /**
             * @param {string} newConnectionId
             */
            handler(newConnectionId) {
                this.fetchConnection(newConnectionId);
            }
        }
    },

    created() {
        this.updateLastMigrationDate();
    },

    methods: {
        updateLastMigrationDate() {
            const params = {
                limit: 1,
                criteria: CriteriaFactory.equals('status', 'finished'),
                sortBy: 'createdAt',
                sortDirection: 'desc'
            };

            this.migrationRunStore.getList(params).then((res) => {
                if (res && res.items.length > 0) {
                    this.lastMigrationDate = res.items[0].createdAt;
                } else {
                    this.lastMigrationDate = '-';
                }
            });
        },

        /**
         * @param {string} connectionId
         */
        fetchConnection(connectionId) {
            this.migrationConnectionStore.getByIdAsync(connectionId).then((connection) => {
                delete connection.credentialFields;
                this.connection = connection;
            });
        },

        onClickEditConnectionCredentials() {
            this.$router.push({
                name: 'swag.migration.wizard.credentials',
                params: {
                    connectionId: this.migrationProcessStore.state.connectionId
                }
            });
        },

        onClickCreateConnection() {
            this.$router.push({
                name: 'swag.migration.wizard.connectionCreate'
            });
        },

        onClickSelectConnection() {
            this.$router.push({
                name: 'swag.migration.wizard.connectionSelect'
            });
        },

        onClickRemoveConnectionCredentials() {
            this.migrationService.updateConnectionCredentials(
                this.migrationProcessStore.state.connectionId,
                null
            ).then(() => {
                this.$router.go(); // Refresh the page
            });
        }
    }
});
