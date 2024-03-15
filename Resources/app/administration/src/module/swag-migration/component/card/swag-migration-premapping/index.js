import template from './swag-migration-premapping.html.twig';
import './swag-migration-premapping.scss';

const { Component, State } = Shopware;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();
const { debounce } = Shopware.Utils;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-premapping', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationApiService */
        migrationApiService: 'migrationApiService',
    },

    data() {
        return {
            isLoading: false,
            premappingInput: [],
        };
    },

    computed: {
        ...mapState('swagMigration', [
            'unfilledPremapping',
            'filledPremapping',
            'premapping',
            'dataSelectionIds',
        ]),

        ...mapGetters('swagMigration', [
            'isPremappingValid',
        ]),

        displayUnfilledPremapping() {
            return this.unfilledPremapping.length > 0;
        },

        displayFilledPremapping() {
            return this.filledPremapping.length > 0;
        },
    },

    watch: {
        dataSelectionIds() {
            this.fetchPremapping();
        },
    },

    methods: {
        fetchPremapping() {
            this.isLoading = true;

            return this.migrationApiService.generatePremapping(this.dataSelectionIds).then((premapping) => {
                State.commit('swagMigration/setPremapping', premapping);

                return this.savePremapping();
            }).then(() => {
                this.isLoading = false;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        async savePremapping() {
            if (this.premapping && this.premapping.length > 0) {
                await this.migrationApiService.writePremapping(this.premapping);
            }
        },

        onPremappingChanged() {
            debounce(async () => {
                await this.savePremapping();
            }, 500)();
        },
    },
});
