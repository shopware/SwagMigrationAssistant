import template from './swag-migration-premapping.html.twig';
import './swag-migration-premapping.scss';
import type { MigrationPremapping } from '../../../../../type/types';

const { Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();
const { debounce } = Shopware.Utils;

export interface SwagMigrationPremappingData {
    isLoading: boolean;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'migrationApiService',
    ],

    data(): SwagMigrationPremappingData {
        return {
            isLoading: false,
        };
    },

    computed: {
        ...mapState(
            () => Store.get('swagMigration'),
            [
                'premapping',
                'dataSelectionIds',
                'isPremappingValid',
            ],
        ),
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

            this.migrationApiService
                .generatePremapping(this.dataSelectionIds)
                .then(async (premapping) => {
                    Store.get('swagMigration').setPremapping(premapping);
                    await this.savePremapping();
                })
                .finally(() => {
                    Store.get('swagMigration').setIsLoading(false);
                    this.isLoading = false;
                });
        },

        async savePremapping() {
            if (!this.premapping || this.premapping.length === 0) {
                return;
            }

            const filledOut = this.premapping.every((group: MigrationPremapping) =>
                group.mapping.every(
                    (mapping) =>
                        mapping.destinationUuid !== null &&
                        mapping.destinationUuid !== undefined &&
                        mapping.destinationUuid !== '',
                ),
            );

            if (!filledOut) {
                return;
            }

            await this.migrationApiService.writePremapping(this.premapping);
        },

        async onPremappingChanged() {
            Store.get('swagMigration').setIsLoading(true);

            debounce(async () => {
                await this.savePremapping();
                Store.get('swagMigration').setIsLoading(false);
            }, 500)();
        },
    },
});
