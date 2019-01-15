import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard-page-profile.html.twig';
import './swag-migration-wizard-page-profile.less';

Component.register('swag-migration-wizard-page-profile', {
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
