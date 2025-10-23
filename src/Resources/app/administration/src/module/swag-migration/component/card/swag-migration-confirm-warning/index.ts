import template from './swag-migration-confirm-warning.html.twig';
import './swag-migration-confirm-warning.scss';
import { MIGRATION_STORE_ID } from '../../../store/migration.store';

const { mapState } = Shopware.Component.getComponentHelper();

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        sourceSystemCurrency() {
            return this.environmentInformation.sourceSystemCurrency;
        },

        targetSystemCurrency() {
            return this.environmentInformation.targetSystemCurrency;
        },

        sourceSystemLanguage() {
            return this.environmentInformation.sourceSystemLocale;
        },

        targetSystemLanguage() {
            return this.environmentInformation.targetSystemLocale;
        },

        ...mapState(
            () => Shopware.Store.get(MIGRATION_STORE_ID),
            [
                'environmentInformation',
                'hasCurrencyMismatch',
                'hasLanguageMismatch',
            ],
        ),
    },
});
