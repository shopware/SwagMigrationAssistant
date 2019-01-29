import { Component } from 'src/core/shopware';
import template from './swag-migration-loading-screen.html.twig';
import './swag-migration-loading-screen.less';

Component.register('swag-migration-loading-screen', {
    template,

    props: {
        profileName: {
            type: String
        },
        entityGroups: {
            type: Array,
            required: true
        },
        statusIndex: {
            type: Number,
            default: 0,
            required: false
        }
    },

    data() {
        return {
            status: ['fetchData', 'writeData', 'downloadMedia']
        };
    },

    computed: {
        progressBarCount() {
            return this.entityGroups.length;
        },

        progressBarContainerGridStyle() {
            let style = '';
            for (let i = 0; i < this.progressBarCount; i += 1) {
                style = `${style} 1fr`;
            }

            return style;
        },

        currentStatus() {
            return this.status[this.statusIndex];
        },

        statusCount() {
            return this.status.length - 1;
        },

        statusShort() {
            return `${this.$t('swag-migration.index.loadingScreenCard.cardTitle', {
                step: this.statusIndex + 1,
                total: this.statusCount
            })} - ${this.$t(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.short`)}`;
        },

        statusLong() {
            return this.$t(
                `swag-migration.index.loadingScreenCard.status.${this.currentStatus}.long`,
                { profileName: this.profileName }
            );
        },

        title() {
            return this.$t('swag-migration.index.loadingScreenCard.cardTitle', {
                step: this.statusIndex + 1,
                total: this.statusCount
            });
        }
    }
});
