import { Component } from 'src/core/shopware';
import template from './swag-migration-result-screen.html.twig';
import './swag-migration-result-screen.scss';

Component.register('swag-migration-result-screen', {
    template,

    props: {
        title: {
            type: String,
            required: true
        },

        cardTitle: {
            type: String,
            default() {
                return this.$tc('swag-migration.index.loadingScreenCard.result.warning.title');
            },
            required: false
        },

        imagePath: {
            type: String,
            default: '',
            required: false
        },

        errorList: {
            type: Array,
            default() {
                return [];
            },
            required: false
        }
    },

    computed: {
        hasErrors() {
            return this.errorList.length > 0;
        },

        svgClasses() {
            return {
                'swag-migration-result-screen__image--is-last': !this.hasErrors
            };
        }
    }
});
