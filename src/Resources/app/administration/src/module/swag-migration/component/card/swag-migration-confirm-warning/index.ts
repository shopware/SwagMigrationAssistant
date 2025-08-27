import template from './swag-migration-confirm-warning.html.twig';
import './swag-migration-confirm-warning.scss';
import { MIGRATION_STORE_ID } from '../../../store/migration.store';

const { Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

/**
 * @private
 */
export interface SwagMigrationConfirmWarningData {
    isCurrencyChecked: boolean;
    isLanguageChecked: boolean;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    data(): SwagMigrationConfirmWarningData {
        return {
            isCurrencyChecked: false,
            isLanguageChecked: false,
        };
    },

    computed: {
        ...mapState(
            () => Store.get(MIGRATION_STORE_ID),
            [
                'environmentInformation',
            ],
        ),

        hasDifferentCurrency() {
            return this.sourceSystemCurrency !== this.targetSystemCurrency;
        },

        sourceSystemCurrency() {
            return this.environmentInformation.sourceSystemCurrency;
        },

        targetSystemCurrency() {
            return this.environmentInformation.targetSystemCurrency;
        },

        hasDifferentLanguage() {
            return this.sourceSystemLanguage !== this.targetSystemLanguage;
        },

        sourceSystemLanguage() {
            return this.environmentInformation.sourceSystemLocale;
        },

        targetSystemLanguage() {
            return this.environmentInformation.targetSystemLocale;
        },

        isContinuable() {
            return (
                (!this.hasDifferentCurrency || this.isCurrencyChecked) &&
                (!this.hasDifferentLanguage || this.isLanguageChecked)
            );
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.onCheckboxValueChanged();
        },

        onCheckboxValueChanged() {
            Store.get(MIGRATION_STORE_ID).setWarningConfirmed(this.isContinuable);
        },
    },
});
