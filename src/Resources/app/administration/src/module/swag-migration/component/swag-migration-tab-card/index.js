import template from './swag-migration-tab-card.html.twig';
import './swag-migration-tab-card.scss';

const { Component } = Shopware;

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
 * @package services-settings
 */
Component.register('swag-migration-tab-card', {
    template,

    props: {
        title: {
            type: String,
            default: '',
            required: false,
        },
        items: {
            type: Array,
            required: true,
        },
    },

    data() {
        return {
            selectedNumber: '',
        };
    },

    computed: {
        tabItems() {
            return this.$refs.swTabsItems;
        },
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        mountedComponent() {
            this.$nextTick(() => {
                // select first tab
                if (this.tabItems !== undefined && this.tabItems.length > 0) {
                    this.selectedNumber = this.tabItems[0].name;
                    setTimeout(() => {
                        if (this.$refs.swTabs) {
                            this.$refs.swTabs.setActiveItem(this.tabItems[0]);
                        }
                    });
                }
            });
        },

        onNewActiveItem(item) {
            this.$refs.contentContainer[this.selectedNumber].setActive(false);
            this.selectedNumber = item.name;
            this.$refs.contentContainer[this.selectedNumber].setActive(true);
        },

        getErrorCountForGroupTab(group) {
            return group.mapping.reduce((currentValue, mapping) => {
                if (!mapping.destinationUuid) {
                    return currentValue + 1;
                }

                return currentValue;
            }, 0);
        },

        getKey(item) {
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
