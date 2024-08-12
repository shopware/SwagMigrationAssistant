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
            'premapping',
            'dataSelectionIds',
        ]),

        ...mapGetters('swagMigration', [
            'isPremappingValid',
        ]),
    },

    watch: {
        dataSelectionIds() {
            this.fetchPremapping();
        },
    },

    methods: {
        fetchPremapping() {
            State.commit('swagMigration/setIsLoading', true);
            this.isLoading = true;

            return this.migrationApiService.generatePremapping(this.dataSelectionIds)
                .then((premapping) => {
                    State.commit('swagMigration/setPremapping', premapping);
                    return this.savePremapping();
                }).finally(() => {
                    State.commit('swagMigration/setIsLoading', false);
                    this.isLoading = false;
                });
        },

        async savePremapping() {
            if (!this.premapping || this.premapping.length === 0) {
                return;
            }

            const filledOut = this.premapping.every(
                (group) => group.mapping.every(
                    (mapping) => mapping.destinationUuid !== null &&
                        mapping.destinationUuid !== undefined &&
                        mapping.destinationUuid !== '',
                ),
            );

            if (!filledOut) {
                return;
            }

            await this.migrationApiService.writePremapping(this.premapping);
        },

        onPremappingChanged() {
            State.commit('swagMigration/setIsLoading', true);
            debounce(async () => {
                await this.savePremapping();
                State.commit('swagMigration/setIsLoading', false);
            }, 500)();
        },
    },
});
