import { Component, State } from 'src/core/shopware';
import template from './swag-migration-shop-information.html.twig';
import './swag-migration-shop-information.less';

const BADGE_TYPE = Object.freeze({
    SUCCESS: 'success',
    DANGER: 'danger'
});

Component.register('swag-migration-shop-information', {
    template,

    inject: ['migrationService'],

    props: {
        environmentInformation: {
            type: Object,
            required: true
        },

        connection: {
            type: Object,
            required: true
        },

        connected: {
            type: Boolean,
            default: false
        },

        lastMigrationDate: {
            type: String,
            default: '-'
        },

        isMigrating: {
            type: Boolean
        },

        isPaused: {
            type: Boolean
        },

        isOtherMigrationRunning: {
            type: Boolean
        }
    },

    data() {
        return {
            showMoreInformation: true,
            showConfirmModal: false
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
        localeStore() {
            return State.getStore('adminLocale');
        },

        migrationGeneralSettingStore() {
            return State.getStore('swag_migration_general_setting');
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
                return 'is--ssl';
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
            return this.connection.profile.gatewayName;
        },

        lastMigrationDateString() {
            const dateObj = new Date(this.lastMigrationDate);
            return dateObj.toLocaleString(this.localeStore.locale, {
                day: 'numeric',
                month: 'numeric',
                year: 'numeric'
            });
        },

        lastMigrationTimeString() {
            const dateObj = new Date(this.lastMigrationDate);
            return dateObj.toLocaleString(this.localeStore.locale, {
                hour: 'numeric',
                minute: 'numeric'
            });
        },

        lastMigrationDateTimeParams() {
            return {
                date: this.lastMigrationDateString,
                time: this.lastMigrationTimeString
            };
        }
    },

    methods: {
        onClickEditConnectionCredentials() {
            this.$router.push({
                name: 'swag.migration.wizard.credentials',
                params: {
                    connection: this.connection
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
            this.migrationService.updateConnectionCredentials(this.connection.id, null).then(() => {
                this.$router.go(); // Refresh the page
            });
        }
    }
});
