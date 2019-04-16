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
            lastConnectionCheck: '-',
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
            return this.migrationProcessStore.state.environmentInformation === null ? {} :
                this.migrationProcessStore.state.environmentInformation;
        },

        connectionName() {
            return this.connection === null ? '' :
                this.connection.name;
        },

        shopUrl() {
            return this.environmentInformation.sourceSystemDomain === undefined ? '' :
                this.environmentInformation.sourceSystemDomain.replace(/^\s*https?:\/\//, '');
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
            return (this.shopUrlPrefix === 'https://');
        },

        shopUrlPrefixClass() {
            if (this.sslActive) {
                return 'swag-migration-shop-information__shop-domain-prefix--is-ssl';
            }

            return '';
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
            return this.environmentInformation.sourceSystemName === undefined ? 'S' :
                this.environmentInformation.sourceSystemName[0];
        },

        profile() {
            return this.connection === null ? '' :
                this.connection.profile.name;
        },

        gateway() {
            return this.connection === null ? '' :
                this.connection.profile.gatewayName;
        },

        lastConnectionCheckDateTimeParams() {
            return {
                date: this.getDateString(this.lastConnectionCheck),
                time: this.getTimeString(this.lastConnectionCheck)
            };
        },

        lastMigrationDateTimeParams() {
            return {
                date: this.getDateString(this.lastMigrationDate),
                time: this.getTimeString(this.lastMigrationDate)
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
                this.lastConnectionCheck = new Date();
            });
        },

        getTimeString(date) {
            return format.date(date, {
                day: undefined,
                month: undefined,
                year: undefined,
                hour: 'numeric',
                minute: '2-digit'
            });
        },

        getDateString(date) {
            return format.date(date);
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
