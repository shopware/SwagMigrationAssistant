/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    props: {
        customSelectionCount: {
            type: Number,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            lastSelectionCount: 0,
        };
    },

    computed: {
        selectionCount(): number {
            if (this.isLoading && this.lastSelectionCount > 0) {
                return this.lastSelectionCount;
            }

            return this.customSelectionCount !== null ? this.customSelectionCount : Object.values(this.selection).length;
        },
    },

    watch: {
        customSelectionCount: {
            handler(newVal: number | null) {
                if (!this.isLoading && newVal !== null && newVal > 0) {
                    this.lastSelectionCount = newVal;
                }
            },
            immediate: true,
        },
    },
});
