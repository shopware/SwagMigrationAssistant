import { Component } from 'src/core/shopware';
import template from './swag-migration-flow-chart.html.twig';

/**
 * This flow chart component need flow-items inside it's slot.
 * To control the current position use the `itemIndex` property.
 * To change the varaint of the current position you can use the `itemVariant` property.
 * To load specific variants for multiple items you can use the property `iinitialItemVaraints`
 * with an array of variants.
 *
 * Usage example:
 * <swag-migration-flow-chart :itemIndex="flowChartItemIndex"
 *                            :itemVariant="flowChartItemVariant"
 *                            :initialItemVariants="flowChartInitialItemVariants">
 *   <swag-migration-flow-item>
 *      Check
 *   </swag-migration-flow-item>
 *   <swag-migration-flow-item>
 *      Read
 *   </swag-migration-flow-item>
 *   <swag-migration-flow-item disabledIcon="small-default-checkmark-line-medium">
 *      Finish
 *   </swag-migration-flow-item>
 * </swag-migration-flow-chart>
 */
Component.register('swag-migration-flow-chart', {
    template,

    props: {
        itemIndex: {
            type: Number,
            required: true
        },
        itemVariant: {
            type: String,
            required: true
        },
        initialItemVariants: {
            type: Array,
            default() {
                return [];
            },
            required: false
        }
    },

    data() {
        return {
            items: []
        };
    },

    mounted() {
        // read child flow items
        this.$children.forEach((child) => {
            if (child._name === '<SwagMigrationFlowItem>') {
                this.items.push(child);
            }
        });

        this.setItemVariants(this.initialItemVariants);
        this.setItemActive(this.itemIndex, true);
        this.setVariantForCurrentItem(this.itemVariant);
    },

    watch: {
        itemIndex(newIndex, oldIndex) {
            this.setItemActive(oldIndex, false);
            this.setItemActive(newIndex, true);
            this.setVariantForCurrentItem(this.itemVariant);
        },
        itemVariant(newVariant) {
            this.setVariantForCurrentItem(newVariant);
        },
        initialItemVariants: {
            deep: true,
            handler(newItemVariants) {
                this.setItemVariants(newItemVariants);
            }
        }
    },

    methods: {
        setItemVariants(itemVariants) {
            const max = Math.min(this.items.length, itemVariants.length);
            for (let i = 0; i < max; i += 1) {
                this.items[i].setVariant(itemVariants[i]);
            }
        },

        setVariantForCurrentItem(variant) {
            this.items[this.itemIndex].setVariant(variant);
        },

        setItemActive(index, active) {
            this.items[index].setActive(active);
        }
    }
});
