import { Component, State } from 'src/core/shopware';
import template from './swag-migration-shop-information.html.twig';
import './swag-migration-shop-information.less';

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

        lastMigrationDate: {
            type: String,
            default: '-'
        },

        isMigrating: {
            type: Boolean
        },

        isPaused: {
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

        shopUrl() {
            return this.profile.credentialFields.endpoint.replace(/^\s*https?:\/\//, '');
        },

        shopUrlPrefix() {
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
    }
});
