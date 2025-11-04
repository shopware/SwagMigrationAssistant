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
        convertedData() {
            if (!this.selectedLog?.convertedData) {
                return '';
            }

            return JSON.stringify(this.selectedLog.convertedData, null, 2);
        },

        sourceData() {
            if (!this.selectedLog?.sourceData) {
                return '';
            }

            return JSON.stringify(this.selectedLog.sourceData, null, 2);
        },

        modalTitle() {
            return this.$tc('swag-migration.index.error-resolution.modals.details.title', {
                entityName: this.entityName,
            });
        },
    },
});
