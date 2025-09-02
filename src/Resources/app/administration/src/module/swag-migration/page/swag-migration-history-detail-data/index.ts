import template from './swag-migration-history-detail-data.html.twig';
import './swag-migration-history-detail-data.scss';
import type { TEntity } from '../../../../type/types';

const { Mixin } = Shopware;

type EntityGroup = {
    id: string;
    name: string;
    total?: number;
};

/**
 * @private
 */
export interface SwagMigrationHistoryDetailData {
    isLoading: boolean;
    allMigrationData: EntityGroup[];
    migrationData: EntityGroup[];
    sortBy: string;
    sortDirection: string;
    disableRouteParams: boolean;
    limit: number;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    mixins: [
        Mixin.getByName('listing'),
    ],

    props: {
        migrationRun: {
            type: Object as PropType<TEntity<'swag_migration_run'>>,
            required: true,
        },
    },

    data(): SwagMigrationHistoryDetailData {
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

        entityGroups(): EntityGroup[] {
            if (!this.migrationRun.progress) {
                return [];
            }

            return this.migrationRun.progress.dataSets.map((entitySelection) => {
                let name: string = entitySelection.entityName;

                if (this.$te(`swag-migration.index.selectDataCard.entities.${name}`)) {
                    name = this.$tc(`swag-migration.index.selectDataCard.entities.${name}`);
                }

                return {
                    name,
                    id: entitySelection.entityName as string,
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
