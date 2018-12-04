import { Component } from 'src/core/shopware';
import template from './swag-migration-index.html.twig';

Component.register('swag-migration-index', {
    template,

    data() {
        return {
            abortButtonVisible: false,
            backButtonVisible: false,
            migrateButtonVisible: false,
            migrateButtonDisabled: false,
            pauseButtonVisible: false,
            continueButtonVisible: false
        };
    },

    /**
     * If the URL changes every button is invisible until
     * the child component says something else afterwards.
     *
     * @param to
     * @param from
     * @param next
     */
    beforeRouteUpdate(to, from, next) {
        this.abortButtonVisible = false;
        this.backButtonVisible = false;
        this.migrateButtonVisible = false;
        this.migrateButtonDisabled = false;
        this.pauseButtonVisible = false;
        this.continueButtonVisible = false;

        next();
    },

    methods: {
        /**
         * Sets the local data variable dynamically to a given value.
         * This is used to allow the child router view component (contentComponent)
         * to modify the visibility and interactivity of the action buttons via event.
         *
         * @param varName
         * @param state
         */
        onActionButtonStateChanged(varName, state) {
            this[varName] = state;
        },

        /**
         * Calls methods on the child router view component (contentComponent) dynamically
         * if existing. This is used to trigger some method on the child via action button.
         *
         * @param methodName
         */
        onActionButtonClick(methodName) {
            if (this.$refs.contentComponent[methodName] !== undefined) {
                this.$refs.contentComponent[methodName]();
            }
        }
    }
});
