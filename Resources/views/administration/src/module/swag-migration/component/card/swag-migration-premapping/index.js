import { Component } from 'src/core/shopware';
import template from './swag-migration-premapping.html.twig';

Component.register('swag-migration-premapping', {
    template,

    props: {
        premapping: {
            type: Array,
            required: true
        }
    },

    created() {
        this.validatePremapping();
    },

    methods: {
        validatePremapping() {
            let isValid = true;
            this.premapping.forEach((group) => {
                group.mapping.forEach((mapping) => {
                    if (mapping.destinationUuid === null || mapping.destinationUuid.length === 0) {
                        isValid = false;
                    }
                });
            });

            this.$emit('onPremappingValid', isValid);
        }
    }
});
