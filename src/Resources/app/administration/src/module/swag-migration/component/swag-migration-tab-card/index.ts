import template from './swag-migration-tab-card.html.twig';
import './swag-migration-tab-card.scss';
import { MIGRATION_STORE_ID } from '../../store/migration.store';

const { mapState } = Shopware.Component.getComponentHelper();

/**
 * @private
 */
export interface SwagMigrationTabCardData {
    selectedNumber: string;
}

type TabCardItem = {
    name: string;
    entity: string | null;
    mapping: { destinationUuid: string | null }[];
};

type GroupTab = {
    mapping: { destinationUuid: string | null }[];
};

/**
 * @example
 * <swag-migration-tab-card>
 *  <swag-migration-tab-card-item title="Item 1">
 *      My item content 1
 *  </swag-migration-tab-card-item>
 *  <swag-migration-tab-card-item title="Item 2">
 *      My item content 2
 *  </swag-migration-tab-card-item>
 * </swag-migration-tab-card>
 *
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        title: {
            type: String,
            default: '',
            required: false,
        },
        items: {
            type: Array as PropType<TabCardItem[]>,
            required: true,
        },
    },

    data(): SwagMigrationTabCardData {
        return {
            selectedNumber: '',
        };
    },

    computed: {
        tabItems() {
            return this.$refs.swTabsItems as TabCardItem[] | undefined;
        },

        ...mapState(
            () => Shopware.Store.get(MIGRATION_STORE_ID),
            [
                'isPremappingValid',
            ],
        ),
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        mountedComponent() {
            this.$nextTick(() => {
                // select first tab
                if (this.tabItems[0]?.name) {
                    this.selectedNumber = this.tabItems[0].name;

                    setTimeout(() => {
                        if (this.$refs.swTabs) {
                            this.$refs.swTabs.setActiveItem(this.tabItems[0]);
                        }
                    }, 100);
                }
            });
        },

        onNewActiveItem(item: TabCardItem) {
            this.$refs.contentContainer[this.selectedNumber].setActive(false);
            this.selectedNumber = item.name;
            this.$refs.contentContainer[this.selectedNumber].setActive(true);
        },

        getErrorCountForGroupTab(group: GroupTab) {
            return group.mapping.reduce((currentValue, mapping) => {
                if (!mapping.destinationUuid) {
                    return currentValue + 1;
                }

                return currentValue;
            }, 0) as number;
        },

        getKey(item: TabCardItem) {
            if (!item.entity) {
                // see https://vuejs.org/api/built-in-special-attributes.html#key
                // we use child components with state
                // means not having a proper unique identifier for each tab likely causes issues.
                // For example the child components may not be properly destroyed and created and just
                // "patched" in place with a completely different tab
                console.error(
                    'swag-migration-tab-card item without `entity` property',
                    item,
                    'more info here: https://vuejs.org/api/built-in-special-attributes.html#key',
                );
                return undefined;
            }

            return item.entity;
        },
    },
});
