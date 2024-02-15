import template from './swag-migration-main-page.html.twig';
import './swag-migration-main-page.scss';

const { Component } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-main-page', {
    template,

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        ...mapState('swagMigration', [
            'environmentInformation',
            'connectionId',
            'isLoading',
        ]),

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
