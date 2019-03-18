import { Component } from 'src/core/shopware';
import utils from 'src/core/service/util.service';
import template from './swag-migration-tab-card-item.html.twig';

Component.register('swag-migration-tab-card-item', {
    template,

    props: {
        title: {
            type: String,
            required: true
        },

        isGrid: {
            type: Boolean,
            default: false,
            required: false
        }
    },

    data() {
        return {
            id: utils.createId(),
            active: false
        };
    },

    methods: {
        setActive(active) {
            this.active = active;
        }
    }
});
