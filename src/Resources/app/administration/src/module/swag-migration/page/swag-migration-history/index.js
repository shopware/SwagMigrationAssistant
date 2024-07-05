import template from './swag-migration-history.html.twig';
import './swag-migration-history.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-history', {
    template,

    inject: {
        repositoryFactory: 'repositoryFactory',
        /** @var {MigrationApiService} migrationApiService */
        migrationApiService: 'migrationApiService',
    },

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            isLoading: false,
            migrationRuns: [],
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
            runIdForRunClear: '',
            showRunClearConfirmModal: false,
            runClearConfirmModalIsLoading: false,
            isMediaProcessing: true,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        migrationRunRepository() {
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
        this.migrationApiService.isMediaProcessing().then((response) => {
            this.isMediaProcessing = response.data;
        });
        this.logDownloadEndpoint = '/api/_action/' +
            `${this.migrationApiService.getApiBasePath()}/download-logs-of-run`;
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
                    label: this.$tc('swag-migration.history.profileAndGateway'),
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
                    align: 'right',
                    allowResize: true,
                },
            ];
        },

        getList() {
            this.isLoading = true;

            const params = this.normalizeListingParams(
                this.getMainListingParams(),
            );

            if (JSON.stringify(this.oldParams) === JSON.stringify(params)) {
                // Do not request the data again if the parameters don't change.
                // For example if the detail window (child route) is opened.
                this.isLoading = false;
                return Promise.resolve(this.migrationRuns);
            }

            this.oldParams = params;
            const criteria = new Criteria(params.page, params.limit);
            criteria.addSorting(Criteria.sort(params.sortBy, params.sortDirection, params.naturalSorting));

            return this.migrationRunRepository.search(criteria, this.context).then((runs) => {
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
        normalizeListingParams(params) {
            params.limit = parseInt(params.limit, 10);
            params.page = parseInt(params.page, 10);

            return params;
        },

        onContextDownloadLogFile(runId) {
            this.runIdForLogDownload = runId;
            this.$nextTick(() => {
                this.$refs.downloadLogsOfRunForm.submit();
            });
        },

        clearDataOfRun(runId) {
            this.runClearConfirmModalIsLoading = true;
            return this.migrationApiService.clearDataOfRun(runId).then(() => {
                this.showRunClearConfirmModal = false;
                this.runClearConfirmModalIsLoading = false;
                this.$router.go();
            }).catch(() => {
                this.createNotificationError({
                    message: this.$t(
                        'swag-migration.index.shopInfoCard.resetMigrationConfirmDialog.errorNotification.message',
                    ),
                    growl: true,
                });
            });
        },

        onContextClearRunClicked(runId) {
            this.runIdForRunClear = runId;
            this.showRunClearConfirmModal = true;
        },

        onClearRunConfirmed() {
            this.clearDataOfRun(this.runIdForRunClear);
        },
    },
});
