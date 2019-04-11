import { Component } from 'src/core/shopware';
import template from './swag-migration-flow-item.html.twig';
import './swag-migration-flow-item.scss';

Component.register('swag-migration-flow-item', {
    template,

    props: {
        disabledIcon: {
            type: String,
            default: 'small-default-circle-medium',
            required: false
        }
    },

    data() {
        return {
            variant: 'disabled',
            active: false
        };
    },

    computed: {
        modifierClasses() {
            return [
                `swag-migration-flow-item--${this.variant}`,
                {
                    'swag-migration-flow-item--active': this.active
                }
            ];
        },

        icon() {
            const iconConfig = {
                disabled: this.disabledIcon,
                info: 'small-default-circle-medium',
                error: 'small-default-x-line-medium',
                success: 'small-default-checkmark-line-medium'
            };

            return iconConfig[this.variant];
        }
    },

    methods: {
        setActive(active) {
            this.active = active;
        },

        setVariant(variant) {
            if (!['disabled', 'info', 'error', 'success'].includes(variant)) {
                return;
            }

            this.variant = variant;
        }
    }
});
