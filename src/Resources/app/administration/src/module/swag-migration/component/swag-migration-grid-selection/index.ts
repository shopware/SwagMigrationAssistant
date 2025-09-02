import template from './swag-migration-grid-selection.html.twig';
import './swag-migration-grid-selection.scss';
import type { MigrationPremapping, MigrationPremappingChoice, MigrationPremappingEntity } from '../../../../type/types';

const { Mixin } = Shopware;

/**
 * @private
 */
export interface SwagMigrationGridSelectionData {
    items: MigrationPremapping[];
    disableRouteParams: boolean;
    limit: number;
    paginationSteps: number[];
    selectOptions: Array<{ label: string; value: string }>;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    emits: ['update:value'],

    mixins: [
        Mixin.getByName('listing'),
    ],

    props: {
        choices: {
            type: Array as PropType<MigrationPremappingChoice[]>,
            required: true,
        },
        mapping: {
            type: Array as PropType<MigrationPremapping[]>,
            required: true,
        },
    },

    data(): SwagMigrationGridSelectionData {
        return {
            items: [],
            disableRouteParams: true,
            limit: 10,
            paginationSteps: [
                10,
                20,
                30,
                50,
            ],
            selectOptions: [],
        };
    },

    watch: {
        choices: {
            handler(newChoices: MigrationPremappingChoice[] | null) {
                if (!newChoices) return;

                this.selectOptions = newChoices.map((choice) => ({
                    label: choice.description,
                    value: choice.uuid,
                }));
            },
            deep: true,
            immediate: true,
        },
        mapping() {
            this.getList();
        },
    },

    methods: {
        getList() {
            this.total = this.mapping.length;
            const start = (this.page - 1) * this.limit;
            const end = Math.min(start + this.limit, this.total);
            this.items.length = 0; // clear the items array without creating a new reference

            // Copy the object references into the display items array (for pagination).
            // Note: Array.slice does not work (as it copies)
            for (let i = start; i < end; i += 1) {
                this.items.push(this.mapping[i]);
            }

            return this.items;
        },

        onInput() {
            this.$emit('update:value');
        },

        getClassesAfterValidation(item: MigrationPremappingEntity) {
            const hasError = item.destinationUuid === null || item.destinationUuid.length === 0;
            return { 'has--error': hasError };
        },
    },
});
