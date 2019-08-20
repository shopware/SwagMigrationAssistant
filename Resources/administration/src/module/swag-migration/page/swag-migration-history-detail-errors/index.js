import template from './swag-migration-history-detail-errors.html.twig';
import './swag-migration-history-detail-errors.scss';

const { Component, Mixin } = Shopware;

Component.register('swag-migration-history-detail-errors', {
    template,

    mixins: [
        Mixin.getByName('listing')
    ],

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService'
    },

    props: {
        migrationRun: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            isLoading: true,
            migrationErrors: [],
            sortBy: 'count',
            sortDirection: 'DESC',
            disableRouteParams: true,
            limit: 10,
            downloadUrl: ''
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'code',
                    dataIndex: 'code',
                    label: this.$t('swag-migration.history.detailPage.errorCode'),
                    primary: true,
                    allowResize: true,
                    sortable: false // TODO: change this if the core supports aggregate sorting
                },
                {
                    property: 'count',
                    dataIndex: 'count',
                    label: this.$t('swag-migration.history.detailPage.errorCount'),
                    primary: true,
                    allowResize: true,
                    sortable: false // TODO: change this if the core supports aggregate sorting
                }
            ];
        }
    },

    methods: {
        getList() {
            this.isLoading = true;
            const params = this.getListingParams();

            return this.migrationService.getGroupedLogsOfRun(
                this.migrationRun.id,
                (params.page - 1) * this.limit,
                params.limit,
                params.sortBy,
                params.sortDirection
            ).then((response) => {
                this.total = response.total;
                this.migrationErrors = response.items;
                this.downloadUrl = response.downloadUrl;
                this.isLoading = false;
                return this.migrationErrors;
            });
        },

        getErrorTitleSnippet(item) {
            const snippetKey = item.titleSnippet;
            if (this.$te(snippetKey)) {
                return snippetKey;
            }

            return 'swag-migration.index.error.unknownError';
        }
    }
});
