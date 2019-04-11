import { Component } from 'src/core/shopware';
import template from './swag-migration-history-selected-data.html.twig';

Component.register('swag-migration-history-selected-data', {
    template,

    props: {
        entityGroups: {
            type: Array,
            default: []
        }
    },

    computed: {
        dataSnippets() {
            const snippets = [];
            this.entityGroups.forEach((group) => {
                if (group.id !== 'processMediaFiles') {
                    snippets.push(
                        group.snippet
                    );
                }
            });

            return snippets;
        }
    }
});
