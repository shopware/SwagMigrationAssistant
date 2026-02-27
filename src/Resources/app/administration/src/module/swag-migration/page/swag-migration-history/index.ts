import template from './swag-migration-history.html.twig';
import './swag-migration-history.scss';
import type { TEntityCollection, TRepository } from '../../../../type/types';
import { MIGRATION_API_SERVICE } from '../../../../core/service/api/swag-migration.api.service';

const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;

type Params = {
    limit: string | number;
    page: string | number;
    term: string | undefined;
    sortBy: string | null;
    sortDirection: string;
    naturalSorting: boolean;
};

/**
 * @private
 */
export interface SwagMigrationHistoryData {
    sortBy: string;
    context: unknown;
    isLoading: boolean;
    migrationRuns: TEntityCollection<'swag_migration_run'>;
    sortDirection: string;
    logDownloadEndpoint: string | null;
    runIdForLogDownload: string | null;
    oldParams: Record<
        string,
        {
            page: number;
            limit: number;
            sortBy: string;
            sortDirection: string;
            naturalSorting?: boolean;
        }
    > | null;
    migrationDateOptions: {
        hour: string;
        minute: string;
        second: string;
    };
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
        'repositoryFactory',
    ],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
    ],

    data(): SwagMigrationHistoryData {
        return {
            isLoading: false,
            migrationRuns: [] as TEntityCollection<'swag_migration_run'>,
            migrationDateOptions: {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            },
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            oldParams: {},
            context: Shopware.Context.api,
            logDownloadEndpoint: '',
            runIdForLogDownload: '',
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        migrationRunRepository(): TRepository<'swag_migration_run'> {
            return this.repositoryFactory.create('swag_migration_run');
        },

        migrationColumns() {
            return this.getMigrationColumns();
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    },

    created() {
        this.logDownloadEndpoint = `/api/_action/${this.migrationApiService.getApiBasePath()}/download-logs-of-run`;
    },

    methods: {
        getMigrationColumns() {
            return [
                {
                    property: 'connection.name',
                    dataIndex: 'connection.name',
                    label: this.$tc('swag-migration.history.connectionName'),
                    primary: true,
                    allowResize: true,
                },
                {
                    property: 'environmentInformation.sourceSystemDomain',
                    dataIndex: 'environmentInformation.sourceSystemDomain',
                    label: this.$tc('swag-migration.history.shopDomain'),
                    visible: false,
                    allowResize: true,
                },
                {
                    property: 'environmentInformation.sourceSystemName',
                    dataIndex: 'environmentInformation.sourceSystemName',
                    label: this.$tc('swag-migration.history.shopSystem'),
                    visible: false,
                    allowResize: true,
                },
                {
                    property: 'connection.profile',
                    dataIndex: 'connection.profileName',
                    label: this.$tc('swag-migration.history.profile'),
                    allowResize: true,
                },
                {
                    property: 'connection.gateway',
                    dataIndex: 'connection.gatewayName',
                    label: this.$tc('swag-migration.history.gateway'),
                    allowResize: true,
                },
                {
                    property: 'step',
                    dataIndex: 'step',
                    label: this.$tc('swag-migration.history.status'),
                    align: 'center',
                    allowResize: true,
                },
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    label: this.$tc('swag-migration.history.importDateTime'),
                    allowResize: true,
                },
            ];
        },

        getList() {
            this.isLoading = true;

            const params = this.normalizeListingParams(this.getMainListingParams());

            if (JSON.stringify(this.oldParams) === JSON.stringify(params)) {
                // Do not request the data again if the parameters don't change.
                // For example if the detail window (child route) is opened.
                this.isLoading = false;
                return Promise.resolve(this.migrationRuns);
            }

            this.oldParams = params;
            const criteria = new Criteria(params.page, params.limit).addSorting(
                Criteria.sort(params.sortBy, params.sortDirection, params.naturalSorting),
            );

            return this.migrationRunRepository
                .search(criteria, this.context)
                .then((runs: TEntityCollection<'swag_migration_run'>) => {
                    this.total = runs.total;
                    this.migrationRuns = runs;
                    this.isLoading = false;

                    return this.migrationRuns;
                });
        },

        /**
         * This will convert string values to int values in the param object.
         * It is needed because Vue Routers '$router.go(-1)' method will mix up
         * the types of the original params object for integers to strings.
         *
         * @param {Object} params
         * @returns {Object}
         */
        normalizeListingParams(params: Params): Params {
            if (typeof params.limit === 'string') {
                params.limit = parseInt(params.limit, 10);
            }
            if (typeof params.page === 'string') {
                params.page = parseInt(params.page, 10);
            }

            return params;
        },

        onContextDownloadLogFile(runId: string) {
            this.runIdForLogDownload = runId;
            this.$nextTick(() => {
                this.$refs.downloadLogsOfRunForm.submit();
            });
        },
    },
});
