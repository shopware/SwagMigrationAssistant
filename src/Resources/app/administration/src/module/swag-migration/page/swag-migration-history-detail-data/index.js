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
            if (!this.migrationRun.progress) {
                return [];
            }

            return this.migrationRun.progress.dataSets.map((entitySelection) => {
                let name = entitySelection.entityName;
                if (this.$te(`swag-migration.index.selectDataCard.entities.${name}`)) {
                    name = this.$tc(`swag-migration.index.selectDataCard.entities.${name}`);
                }

                return {
                    id: entitySelection.entityName,
                    name,
                    total: entitySelection.total,
                };
            });
        },
    },

    methods: {
        getList() {
            this.isLoading = true;

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
