import template from './swag-migration-premapping.html.twig';
import './swag-migration-premapping.scss';

const { Component, Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();
const { debounce } = Shopware.Utils;

/**
 * @private
 * @sw-package fundamentals@after-sales
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
        ...mapState(() => Store.get('swagMigration'), [
            'premapping',
            'dataSelectionIds',
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
            Store.get('swagMigration').setIsLoading(true);
            this.isLoading = true;

            return this.migrationApiService.generatePremapping(this.dataSelectionIds)
                .then((premapping) => {
                    Store.get('swagMigration').setPremapping(premapping);
                    return this.savePremapping();
                }).finally(() => {
                    Store.get('swagMigration').setIsLoading(false);
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
            Store.get('swagMigration').setIsLoading(true);
            debounce(async () => {
                await this.savePremapping();
                Store.get('swagMigration').setIsLoading(false);
            }, 500)();
        },
    },
});
