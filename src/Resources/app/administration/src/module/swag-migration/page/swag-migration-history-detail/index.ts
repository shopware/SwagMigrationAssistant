import template from './swag-migration-history-detail.html.twig';
import './swag-migration-history-detail.scss';
import type { TEntity, TRepository } from '../../../../type/types';
import { MIGRATION_API_SERVICE } from '../../../../core/service/api/swag-migration.api.service';
import { BADGE_TYPE } from '../../component/card/swag-migration-shop-information';

const { Criteria } = Shopware.Data;

/**
 * @private
 */
export const MIGRATION_SUCCESS_STEP = 'finished';

/**
 * @private
 */
export interface SwagMigrationHistoryDetailData {
    runId: string;
    migrationRun: TEntity<'swag_migration_run'> | null;
    showModal: boolean;
    isLoading: boolean;
    migrationDateOptions: {
        hour: string;
        minute: string;
        second: string;
    };
    currentTab: string;
    context: unknown;
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

    data(): SwagMigrationHistoryDetailData {
        return {
            runId: '',
            migrationRun: {} as TEntity<'swag_migration_run'>,
            showModal: true,
            isLoading: true,
            migrationDateOptions: {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            },
            currentTab: 'data',
            context: Shopware.Context.api,
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

        statusBadgeLabel() {
            if (!this.migrationRun?.step) {
                return 'swag-migration.history.detailPage.status.unknown';
            }

            return `swag-migration.history.detailPage.status.${this.migrationRun.step}`;
        },

        statusBadgeVariant() {
            if (this.migrationRun?.step === MIGRATION_SUCCESS_STEP) {
                return BADGE_TYPE.SUCCESS;
            }

            return BADGE_TYPE.DANGER;
        },

        shopFirstLetter() {
            return this.migrationRun.environmentInformation?.sourceSystemName === undefined
                ? 'S'
                : this.migrationRun.environmentInformation.sourceSystemName.charAt(0);
        },

        profileIcon(): string | null {
            return this.migrationRun.connection?.profile?.icon ?? null;
        },

        connectionName(): string {
            return this.migrationRun.connection === null ? '' : this.migrationRun.connection.name;
        },

        shopUrl() {
            return this.migrationRun.environmentInformation.sourceSystemDomain === undefined
                ? ''
                : this.migrationRun.environmentInformation.sourceSystemDomain.replace(/^\s*https?:\/\//, '');
        },

        shopUrlPrefix() {
            if (this.migrationRun.environmentInformation.sourceSystemDomain === undefined) {
                return '';
            }

            const match = this.migrationRun.environmentInformation.sourceSystemDomain.match(/^\s*https?:\/\//);

            if (match === null) {
                return '';
            }

            return match[0];
        },

        sslActive() {
            return this.shopUrlPrefix === 'https://';
        },

        shopUrlPrefixClass() {
            return this.sslActive ? 'swag-migration-shop-information__shop-domain-prefix--is-ssl' : '';
        },

        profileName(): string {
            return this.migrationRun.connection === null ? '' : this.migrationRun.connection.profileName;
        },

        gatewayName(): string {
            return this.migrationRun.connection === null ? '' : this.migrationRun.connection.gatewayName;
        },

        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    },

    created() {
        if (!this.$route.params.id) {
            this.isLoading = false;
            this.onCloseModal();
            return Promise.resolve();
        }

        this.runId = this.$route.params.id;
        const criteria = new Criteria(1, 1);
        criteria.addFilter(Criteria.equals('id', this.runId));

        return this.migrationRunRepository
            .search(criteria, this.context)
            .then((runs) => {
                if (runs.length < 1) {
                    this.isLoading = false;
                    this.onCloseModal();
                    return Promise.resolve();
                }

                this.migrationRun = runs.first();

                return this.migrationApiService
                    .getProfileInformation(
                        this.migrationRun.connection.profileName,
                        this.migrationRun.connection.gatewayName,
                    )
                    .then((profileInformation) => {
                        this.migrationRun.connection.profile = profileInformation.profile;

                        this.isLoading = false;
                        this.$nextTick(() => {
                            this.$refs.tabReference.setActiveItem(this.$refs.dataTabItem);
                        });
                    });
            })
            .catch(() => {
                this.isLoading = false;
                this.onCloseModal();
            });
    },

    methods: {
        onCloseModal() {
            this.showModal = false;
            this.$nextTick(() => {
                this.$router.go(-1);
            });
        },

        newActiveTabItem(item: { name: string }) {
            this.currentTab = item.name;
        },
    },
});
