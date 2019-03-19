import { Component } from 'src/core/shopware';
import template from './swag-migration-result-screen.html.twig';
import './swag-migration-result-screen.scss';

Component.register('swag-migration-result-screen', {
    template,

    props: {
        errorList: {
            type: Array,
            default() {
                return [];
            },
            required: false
        }
    },

    computed: {
        hasErrors() {
            return this.errorList.length > 0;
        },

        title() {
            if (this.hasErrors) {
                return this.$t('swag-migration.index.loadingScreenCard.result.warning.title');
            }

            return this.$t('swag-migration.index.loadingScreenCard.result.success.title');
        },

        caption() {
            if (this.hasErrors) {
                return this.$t('swag-migration.index.loadingScreenCard.result.warning.caption');
            }

            return this.$t('swag-migration.index.loadingScreenCard.result.success.caption');
        }
    }
});
