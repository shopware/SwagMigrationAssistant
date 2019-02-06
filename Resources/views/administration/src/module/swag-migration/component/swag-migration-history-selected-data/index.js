import { Component } from 'src/core/shopware';
import template from './swag-migration-history-selected-data.html.twig';

const ENTITY_GROUP_SNIPPET_LOOKUP = Object.freeze({
    categories_products: 'categoriesAndProducts',
    customers_orders: 'customersAndOrders',
    media: 'media'
});

Component.register('swag-migration-history-selected-data', {
    template,

    props: {
        entityGroups: {
            type: Array,
            default: []
        },
        profile: {
            type: String,
            default: ''
        }
    },

    computed: {
        dataSnippets() {
            const snippets = [];
            this.entityGroups.forEach((group) => {
                const groupSnippetName = ENTITY_GROUP_SNIPPET_LOOKUP[group.id];
                snippets.push(
                    `swag-migration.index.selectDataCard.dataSelection.${this.profile}Profile.${groupSnippetName}`
                );
            });

            return snippets;
        }
    }
});
