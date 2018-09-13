import { Component } from 'src/core/shopware';
import template from './swag-migration-loading-screen.html.twig';
import './swag-migration-loading-screen.less';

Component.register('swag-migration-loading-screen', {
    template,

    props: {
        profileName: {
            type: String
        },
        tableData: {
            type: Array
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
            let count = 0;
            this.tableData.forEach((data) => {
                if (data.selected) {
                    count += 1;
                }
            });

            return count;
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
            const containsMediaGroup = this.tableData.find((data) => {
                return (data.selected) && (
                    data.id === 'media' ||
                    data.id === 'categories_products'
                );
            });
            if (containsMediaGroup !== undefined) {
                return this.status.length;
            }

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
