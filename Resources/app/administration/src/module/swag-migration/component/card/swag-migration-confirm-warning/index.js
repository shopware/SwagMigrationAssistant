import template from './swag-migration-confirm-warning.html.twig';
import './swag-migration-confirm-warning.scss';

const { Component } = Shopware;

Component.register('swag-migration-confirm-warning', {
    template,

    data() {
        return {
            migrationProcessState: this.$store.state['swagMigration/process']
        };
    },

    computed: {
        sourceSystemCurrency() {
            return this.migrationProcessState.environmentInformation.sourceSystemCurrency;
        },

        targetSystemCurrency() {
            return this.migrationProcessState.environmentInformation.targetSystemCurrency;
        }
    },

    methods: {
        onCheckboxValueChanged(value) {
            this.$emit('confirmation-change', value);
        }
    }
});
