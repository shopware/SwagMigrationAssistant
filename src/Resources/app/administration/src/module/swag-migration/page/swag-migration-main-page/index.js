import template from './swag-migration-main-page.html.twig';
import './swag-migration-main-page.scss';

const { Component } = Shopware;

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Component.register('swag-migration-main-page', {
    template,

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        environmentInformation() {
            return Shopware.Store.get('swagMigration').environmentInformation;
        },

        connectionId() {
            return Shopware.Store.get('swagMigration').connectionId;
        },

        isLoading() {
            return Shopware.Store.get('swagMigration').isLoading;
        },

        displayWarnings() {
            return this.environmentInformation.displayWarnings;
        },

        connectionEstablished() {
            return this.environmentInformation !== undefined &&
                this.environmentInformation.requestStatus &&
                (
                    this.environmentInformation.requestStatus.isWarning === true ||
                    (
                        this.environmentInformation.requestStatus.isWarning === false &&
                        this.environmentInformation.requestStatus.code === ''
                    )
                );
        },
    },
});
