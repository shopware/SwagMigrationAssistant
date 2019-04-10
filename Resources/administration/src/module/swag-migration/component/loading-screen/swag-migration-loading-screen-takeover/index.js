import { Component } from 'src/core/shopware';
import template from './swag-migration-loading-screen-takeover.html.twig';
import './swag-migration-loading-screen-takeover.scss';

Component.register('swag-migration-loading-screen-takeover', {
    template,

    props: {
        isOtherInstanceFetching: {
            type: Boolean
        },
        isMigrationInterrupted: {
            type: Boolean
        }
    },

    data() {
        return {
            showTakeoverModal: false
        };
    },

    methods: {
        onTakeoverLinkClick() {
            this.showTakeoverModal = true;
        },

        onCloseTakeoverModal() {
            this.showTakeoverModal = false;
        },

        onTakeover() {
            this.showTakeoverModal = false;
            this.$nextTick(() => {
                // this will remove this component from the DOM so it must be called after the modal ist closed.
                this.$emit('onTakeoverMigration');
            });
        }
    }
});
