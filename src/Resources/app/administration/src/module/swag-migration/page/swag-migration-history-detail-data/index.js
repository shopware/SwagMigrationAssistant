import template from './swag-migration-history-detail-data.html.twig';
import './swag-migration-history-detail-data.scss';

const { Component, Mixin } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-history-detail-data', {
    template,

    mixins: [
        Mixin.getByName('listing'),
    ],

    props: {
        migrationRun: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            isLoading: true,
            allMigrationData: [],
            migrationData: [],
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            disableRouteParams: true,
            limit: 10,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'name',
                    label: this.$tc('swag-migration.history.detailPage.dataName'),
                    primary: true,
                    allowResize: true,
                    sortable: false,
                },
                {
                    property: 'count',
                    label: this.$tc('swag-migration.history.detailPage.dataCount'),
                    allowResize: true,
                    sortable: false,
                },
            ];
        },

        entityGroups() {
            return Object.entries(this.migrationRun.progress.dataSets).map(([name, count]) => {
                return { id: name, name, total: count };
            });
        },
    },

    methods: {
        getList() {
            this.isLoading = true;

            // ToDo MIG-35 - Implement sorting

            this.total = this.entityGroups.length;
            const start = (this.page - 1) * this.limit;
            const end = Math.min(start + this.limit, this.total);
            this.migrationData = [];

            // Copy the object references into the display items array (for pagination). Note: Array.slice dont work
            for (let i = start; i < end; i += 1) {
                this.migrationData.push(this.entityGroups[i]);
            }

            this.isLoading = false;
            return this.items;
        },
    },
});
