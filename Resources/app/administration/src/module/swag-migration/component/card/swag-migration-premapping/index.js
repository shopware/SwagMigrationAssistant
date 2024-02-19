import template from './swag-migration-premapping.html.twig';
import './swag-migration-premapping.scss';

const { Component, State } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();
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
        ...mapState('swagMigration/ui', [
            'unfilledPremapping',
            'filledPremapping',
            'isPremappingValid',
            'premapping',
            'dataSelectionIds',
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

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            State.commit('swagMigration/ui/setIsPremappingValid', false);
        },

        fetchPremapping() {
            this.isLoading = true;

            return this.migrationApiService.generatePremapping(this.dataSelectionIds).then((premapping) => {
                State.commit('swagMigration/ui/setPremapping', premapping);

                return this.savePremapping();
            }).then(() => {
                this.notifyPremappingValidWatchers(
                    this.validatePremapping(),
                );

                this.isLoading = false;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        notifyPremappingValidWatchers(isValid) {
            if (isValid !== this.isPremappingValid) {
                State.commit('swagMigration/ui/setIsPremappingValid', isValid);
                return;
            }

            // It is needed to trigger a watcher event here, even if the value does not have been changed.
            State.commit('swagMigration/ui/setIsPremappingValid', !isValid);
            this.$nextTick(() => {
                State.commit('swagMigration/ui/setIsPremappingValid', isValid);
            });
        },

        validatePremapping() {
            const isValid = !this.premapping.some((group) => {
                return group.mapping.some((mapping) => {
                    return mapping.destinationUuid === null || mapping.destinationUuid.length === 0;
                });
            });

            State.commit('swagMigration/ui/setIsPremappingValid', isValid);

            return isValid;
        },

        async savePremapping() {
            if (this.premapping && this.premapping.length > 0) {
                await this.migrationApiService.writePremapping(this.premapping);
            }
        },

        onPremappingChanged() {
            debounce(async () => {
                await this.savePremapping();
                this.validatePremapping();
            }, 500)();
        },
    },
});
