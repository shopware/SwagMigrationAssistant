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
            required: false
        }
    },

    data() {
        return {
            items: [],
            itemDictionary: {},
            selectedItemName: ''
        };
    },

    computed: {
        tabItems() {
            return this.$refs.swTabsItems;
        },

        cardClasses() {
            if (this.selectedItemName === undefined || this.selectedItemName === '') {
                return {};
            }

            return {
                'sw-card--grid': this.itemDictionary[this.selectedItemName].isGrid
            };
        }
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        mountedComponent() {
            // read tab-card items
            this.$refs.card.$children.forEach((child) => {
                if (child.$options._componentTag === 'swag-migration-tab-card-item') {
                    this.items.push(child);
                    this.itemDictionary[child.id] = child;
                }
            });

            this.$nextTick(() => {
                // let the tabs component know that the content may need a scrollbar
                this.$refs.tabs.checkIfNeedScroll();
                this.$refs.tabs.addScrollbarOffset();

                // select first tab
                if (this.tabItems !== undefined && this.tabItems.length > 0) {
                    this.selectedItemName = this.tabItems[0].name;
                    this.$refs.tabs.setActiveItem(this.tabItems[0]);
                }
            });
        },

        onNewActiveItem(item) {
            this.itemDictionary[this.selectedItemName].setActive(false);
            this.selectedItemName = item.name;
            this.itemDictionary[this.selectedItemName].setActive(true);
        }
    }
});
