import template from './swag-migration-history-detail-errors.html.twig';
import './swag-migration-history-detail-errors.scss';
import type { MigrationError, TEntity } from '../../../../type/types';
import { MIGRATION_API_SERVICE } from '../../../../core/service/api/swag-migration.api.service';

const { Mixin } = Shopware;

/**
 * @private
 */
export interface SwagMigrationHistoryDetailErrorsData {
    isLoading: boolean;
    allMigrationErrors: MigrationError[] | null;
    migrationErrors: MigrationError[];
    sortBy: string;
    sortDirection: string;
    disableRouteParams: boolean;
    limit: number;
    downloadUrl: string;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
    ],

    mixins: [
        Mixin.getByName('listing'),
    ],

    props: {
        migrationRun: {
            type: Object as PropType<TEntity<'swag_migration_run'>>,
            required: true,
        },
    },

    data(): SwagMigrationHistoryDetailErrorsData {
        return {
            isLoading: true,
            allMigrationErrors: null as MigrationError[] | null,
            migrationErrors: [] as MigrationError[],
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

        loadAllMigrationErrors(): Promise<MigrationError[]> {
            return this.migrationApiService.getGroupedLogsOfRun(this.migrationRun.id).then((response) => {
                this.total = response.total;
                this.allMigrationErrors = response.items;

                this.allMigrationErrors.forEach((item) => {
                    item.title = this.$tc(this.getErrorTitleSnippet(item), { entity: item.entity }, 0);
                });

                this.downloadUrl = response.downloadUrl;
                return this.allMigrationErrors;
            });
        },

        applySorting(params: { page: number; sortBy: string; sortDirection: string }) {
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

        getErrorTitleSnippet(item: MigrationError) {
            const snippetKey = `swag-migration.index.error-resolution.codes.${item.code}`;

            if (this.$te(snippetKey)) {
                return snippetKey;
            }

            return 'swag-migration.index.error-resolution.codes.unknown';
        },

        submitDownload() {
            this.$refs.downloadForm.submit();
        },
    },
});
