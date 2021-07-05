import template from './swag-migration-history-selected-data.html.twig';

const { Component } = Shopware;

Component.register('swag-migration-history-selected-data', {
    template,

    props: {
        entityGroups: {
            type: Array,
            default: () => { return []; },
        },
    },

    computed: {
        dataSnippets() {
            const snippets = [];
            this.entityGroups.forEach((group) => {
                if (group.id !== 'processMediaFiles') {
                    snippets.push(
                        group.snippet,
                    );
                }
            });

            return snippets;
        },
    },
});
