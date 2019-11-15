import template from './swag-migration-confirm-warning.html.twig';
import './swag-migration-confirm-warning.scss';

const { Component, StateDeprecated } = Shopware;

Component.register('swag-migration-confirm-warning', {
    template,

    data() {
        return {
            /** @type MigrationProcessStore */
            migrationProcessStore: StateDeprecated.getStore('migrationProcess')
        };
    },

    computed: {
        sourceSystemCurrency() {
            return this.migrationProcessStore.state.environmentInformation.sourceSystemCurrency;
        },

        targetSystemCurrency() {
            return this.migrationProcessStore.state.environmentInformation.targetSystemCurrency;
        }
    },

    methods: {
        onCheckboxValueChanged(value) {
            this.$emit('confirmation-change', value);
        }
    }
});
