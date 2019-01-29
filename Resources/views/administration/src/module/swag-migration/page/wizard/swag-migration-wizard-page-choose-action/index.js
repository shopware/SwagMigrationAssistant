import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-choose-action.html.twig';
import './swag-migration-wizard-page-choose-action.less';

Component.register('swag-migration-wizard-page-choose-action', {
    template,

    props: {
        profileSelection: {
            type: String
        }
    },

    data() {
        return {
            selection: ''
        };
    },

    watch: {
        profileSelection: {
            immediate: true,
            handler(newSelection) {
                this.selection = newSelection;
            }
        },
        selection: {
            handler(newSelection) {
                this.$emit('onProfileSelectionChanged', newSelection);
            }
        }
    }
});
