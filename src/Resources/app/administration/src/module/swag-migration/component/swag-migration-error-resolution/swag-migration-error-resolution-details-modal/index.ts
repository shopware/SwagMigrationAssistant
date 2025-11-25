import template from './swag-migration-error-resolution-details-modal.html.twig';
import type { ResolutionModalRow } from '../swag-migration-error-resolution-modal';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        entityName: {
            type: String,
            required: true,
        },
        selectedLog: {
            type: Object as PropType<ResolutionModalRow>,
            required: true,
        },
    },

    computed: {
        convertedData(): string | null {
            return this.prepareData(this.selectedLog.convertedData);
        },

        sourceData(): string | null {
            return this.prepareData(this.selectedLog.sourceData);
        },

        modalTitle(): string {
            return this.$tc('swag-migration.index.error-resolution.modals.details.title', { entityName: this.entityName });
        },
    },

    methods: {
        prepareData(data: Record<string, unknown> | null | undefined): string | null {
            if (!data) {
                return null;
            }

            const string = JSON.stringify(data, null, 2);

            if (string?.trim() === '{}') {
                return null;
            }

            return string;
        },
    },
});
