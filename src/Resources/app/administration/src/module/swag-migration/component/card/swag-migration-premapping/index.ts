import template from './swag-migration-premapping.html.twig';
import './swag-migration-premapping.scss';
import type { MigrationPremapping } from '../../../../../type/types';
import { MIGRATION_API_SERVICE } from '../../../../../core/service/api/swag-migration.api.service';
import type { MigrationStore } from '../../../store/migration.store';
import { MIGRATION_STORE_ID } from '../../../store/migration.store';

const { Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();
const { debounce } = Shopware.Utils;

/**
 * @private
 */
export interface SwagMigrationPremappingData {
    isLoading: boolean;
    migrationStore: MigrationStore;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_API_SERVICE,
    ],

    data(): SwagMigrationPremappingData {
        return {
            isLoading: false,
            migrationStore: Store.get(MIGRATION_STORE_ID),
        };
    },

    computed: {
        ...mapState(
            () => Store.get(MIGRATION_STORE_ID),
            [
                'premapping',
                'dataSelectionIds',
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
            this.migrationStore.setIsLoading(true);
            this.isLoading = true;

            this.migrationApiService
                .generatePremapping(this.dataSelectionIds)
                .then(async (premapping: MigrationPremapping[]) => {
                    this.migrationStore.setPremapping(premapping);
                    await this.savePremapping();
                })
                .finally(() => {
                    this.migrationStore.setIsLoading(false);
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
            this.migrationStore.setIsLoading(true);

            debounce(async () => {
                await this.savePremapping();
                this.migrationStore.setIsLoading(false);
            }, 500)();
        },
    },
});
