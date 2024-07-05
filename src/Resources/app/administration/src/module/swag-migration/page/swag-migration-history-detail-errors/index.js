import template from './swag-migration-history-detail-errors.html.twig';
import './swag-migration-history-detail-errors.scss';

const { Component, Mixin } = Shopware;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-history-detail-errors', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationApiService */
        migrationApiService: 'migrationApiService',
    },

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
            allMigrationErrors: null,
            migrationErrors: [],
            sortBy: 'level',
            sortDirection: 'DESC',
            disableRouteParams: true,
            limit: 10,
            downloadUrl: '',
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
                    property: 'title',
                    dataIndex: 'title',
                    label: this.$tc('swag-migration.history.detailPage.errorCode'),
                    primary: true,
                    allowResize: true,
                    sortable: true,
                },
                {
                    property: 'level',
                    dataIndex: 'level',
                    label: this.$tc('swag-migration.history.detailPage.errorLevel'),
                    primary: true,
                    allowResize: true,
                    sortable: true,
                },
                {
                    property: 'count',
                    dataIndex: 'count',
                    label: this.$tc('swag-migration.history.detailPage.errorCount'),
                    primary: true,
                    allowResize: true,
                    sortable: true,
                },
            ];
        },
    },

    methods: {
        async getList() {
            this.isLoading = true;
            const params = this.getMainListingParams();

            if (this.allMigrationErrors === null) {
                await this.loadAllMigrationErrors();
            }

            this.applySorting(params);

            const startIndex = (params.page - 1) * this.limit;
            const endIndex = Math.min((params.page - 1) * this.limit + this.limit, this.allMigrationErrors.length);
            this.migrationErrors = [];
            for (let i = startIndex; i < endIndex; i += 1) {
                this.migrationErrors.push(this.allMigrationErrors[i]);
            }

            this.isLoading = false;
            return this.migrationErrors;
        },

        loadAllMigrationErrors() {
            return this.migrationApiService.getGroupedLogsOfRun(
                this.migrationRun.id,
            ).then((response) => {
                this.total = response.total;
                this.allMigrationErrors = response.items;
                this.allMigrationErrors.forEach((item) => {
                    item.title = this.$tc(this.getErrorTitleSnippet(item), 0, { entity: item.entity });
                });
                this.downloadUrl = response.downloadUrl;
                return this.allMigrationErrors;
            });
        },

        applySorting(params) {
            this.allMigrationErrors.sort((first, second) => {
                if (params.sortDirection === 'ASC') {
                    if (first[params.sortBy] < second[params.sortBy]) {
                        return -1;
                    }

                    return 1;
                }

                if (first[params.sortBy] > second[params.sortBy]) {
                    return -1;
                }

                return 1;
            });
        },

        getErrorTitleSnippet(item) {
            const snippetKey = item.titleSnippet;
            if (this.$te(snippetKey)) {
                return snippetKey;
            }

            return 'swag-migration.index.error.unknownError';
        },

        submitDownload() {
            this.$refs.downloadForm.submit();
        },
    },
});
