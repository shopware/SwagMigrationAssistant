import template from './swag-migration-loading-screen.html.twig';
import './swag-migration-loading-screen.scss';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        step: {
            type: String,
            required: true,
        },

        progress: {
            type: Number,
            required: true,
        },

        total: {
            type: Number,
            required: true,
        },
    },

    computed: {
        progressBarLeftPointDescription() {
            return this.$tc(`swag-migration.index.loadingScreenCard.status.${this.step}.short`);
        },

        caption() {
            return this.$tc(`swag-migration.index.loadingScreenCard.status.${this.step}.caption`);
        },

        statusLong() {
            return this.$tc(`swag-migration.index.loadingScreenCard.status.${this.step}.long`);
        },
    },
});
