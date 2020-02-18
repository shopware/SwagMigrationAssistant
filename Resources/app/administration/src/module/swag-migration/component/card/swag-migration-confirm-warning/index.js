import template from './swag-migration-confirm-warning.html.twig';
import './swag-migration-confirm-warning.scss';

const { Component } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-migration-confirm-warning', {
    template,

    computed: {
        ...mapState('swagMigration/process', [
            'environmentInformation'
        ]),

        sourceSystemCurrency() {
            return this.environmentInformation.sourceSystemCurrency;
        },

        targetSystemCurrency() {
            return this.environmentInformation.targetSystemCurrency;
        }
    },

    methods: {
        onCheckboxValueChanged(value) {
            this.$emit('confirmation-change', value);
        }
    }
});
