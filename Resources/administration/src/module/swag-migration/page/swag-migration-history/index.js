import template from './swag-migration-history.html.twig';
import './swag-migration-history.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-migration-history', {
    template,

    inject: {
        repositoryFactory: 'repositoryFactory'
    },

    mixins: [
        Mixin.getByName('listing')
    ],

    data() {
        return {
            isLoading: false,
            migrationRuns: [],
            migrationDateOptions: {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            },
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            oldParams: {},
            context: Shopware.Context.api
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        migrationRunRepository() {
            return this.repositoryFactory.create('swag_migration_run');
        },

        migrationColumns() {
            return this.getMigrationColumns();
        }
    },

    methods: {
        getMigrationColumns() {
            return [
                {
                    property: 'connection.name',
                    dataIndex: 'connection.name',
                    label: this.$tc('swag-migration.history.connectionName'),
                    primary: true,
                    allowResize: true
                },
                {
                    property: 'environmentInformation.sourceSystemDomain',
                    dataIndex: 'environmentInformation.sourceSystemDomain',
                    label: this.$tc('swag-migration.history.shopDomain'),
                    visible: false,
                    allowResize: true
                },
                {
                    property: 'environmentInformation.sourceSystemName',
                    dataIndex: 'environmentInformation.sourceSystemName',
                    label: this.$tc('swag-migration.history.shopSystem'),
                    visible: false,
                    allowResize: true
                },
                {
                    property: 'connection.profile',
                    dataIndex: 'connection.profileName',
                    label: this.$tc('swag-migration.history.profileAndGateway'),
                    allowResize: true
                },
                {
                    property: 'status',
                    dataIndex: 'status',
                    label: this.$tc('swag-migration.history.status'),
                    align: 'center',
                    allowResize: true
                },
                {
                    property: 'progress',
                    dataIndex: 'progress',
                    label: this.$tc('swag-migration.history.selectedData'),
                    visible: false,
                    allowResize: true
                },
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    label: this.$tc('swag-migration.history.importDateTime'),
                    align: 'right',
                    allowResize: true
                }
            ];
        },

        getList() {
            this.isLoading = true;

            const params = this.normalizeListingParams(
                this.getListingParams()
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

        onMigrateButtonClick() {
            this.$router.push({ name: 'swag.migration.index.main', params: { startMigration: true } });
        }
    }
});
