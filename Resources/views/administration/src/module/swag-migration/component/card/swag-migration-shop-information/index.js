import { Component, State } from 'src/core/shopware';
import template from './swag-migration-shop-information.html.twig';
import './swag-migration-shop-information.less';

const BADGE_TYPE = Object.freeze({
    SUCCESS: 'success',
    DANGER: 'danger'
});

Component.register('swag-migration-shop-information', {
    template,

    props: {
        environmentInformation: {
            type: Object,
            default: {}
        },

        profile: {
            type: Object,
            default: {}
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
            showMoreInformation: true
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
                return '';
            }

            return this.profile.credentialFields.endpoint.replace(/^\s*https?:\/\//, '');
        },

        shopUrlPrefix() {
            if (this.environmentInformation.sourceSystemDomain === undefined) {
                return '';
            }

            const match = this.profile.credentialFields.endpoint.match(/^\s*https?:\/\//);
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
            return this.environmentInformation.sourceSystemName;
        },

        shopVersion() {
            return this.environmentInformation.sourceSystemVersion;
        },

        shopFirstLetter() {
            return this.environmentInformation.sourceSystemName[0];
        },

        categoryProductCount() {
            return this.environmentInformation.categoryTotal + this.environmentInformation.productTotal;
        },

        customerOrderCount() {
            return this.environmentInformation.customerTotal + this.environmentInformation.orderTotal;
        },

        mediaCount() {
            return this.environmentInformation.assetTotal;
        },

        gateway() {
            return this.profile.gateway;
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
        onClickEditSettings() {
            this.$router.push({
                name: 'swag.migration.wizard.credentials',
                params: {
                    profile: this.profile
                }
            });
        },

        onClickCreateProfile() {
            this.$router.push({
                name: 'swag.migration.wizard.profileCreate'
            });
        },

        onClickSelectProfile() {
            this.$router.push({
                name: 'swag.migration.wizard.profileSelect'
            });
        },

        onClickRemoveProfile() {
            this.migrationGeneralSettingStore.getList({ limit: 1 }).then((settings) => {
                if (!settings || settings.items.length === 0) {
                    return;
                }

                settings.items[0].selectedProfileId = null;
                settings.items[0].save().then(() => {
                    this.$router.push({
                        name: 'swag.migration.emptyScreen'
                    });
                });
            });
        }
    }
});
