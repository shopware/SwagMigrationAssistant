import template from './swag-migration-tab-card.html.twig';
import './swag-migration-tab-card.scss';

const { Component } = Shopware;

/**
 * Example:
 * <swag-migration-tab-card>
 *  <swag-migration-tab-card-item title="Item 1">
 *      My item content 1
 *  </swag-migration-tab-card-item>
 *  <swag-migration-tab-card-item title="Item 2">
 *      My item content 2
 *  </swag-migration-tab-card-item>
 * </swag-migration-tab-card>
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
            required: true
        }
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
                // let the tabs component know that the content may need a scrollbar
                this.$refs.tabs.checkIfNeedScroll();
                this.$refs.tabs.addScrollbarOffset();

                // select first tab
                if (this.tabItems !== undefined && this.tabItems.length > 0) {
                    this.selectedNumber = this.tabItems[0].name;
                    this.$refs.tabs.setActiveItem(this.tabItems[0]);
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
                if (mapping.destinationUuid === null || mapping.destinationUuid.length === 0) {
                    return currentValue + 1;
                }

                return currentValue;
            }, 0);
        },
    },
});
