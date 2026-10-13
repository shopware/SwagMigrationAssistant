import { defineComponent } from 'vue';

import { MIGRATION_API_SERVICE } from '../../../core/service/api/swag-migration.api.service';

/**
 * @private
 */
export interface SwagMigrationLogDownloadMixinData {
    isLogDownloading: boolean;
}

/**
 * Mixin for downloading a migration run log as a client-side file.
 *
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Mixin.register(
    'swag-migration-log-download',
    defineComponent({
        mixins: [
            Shopware.Mixin.getByName('notification'),
        ],

        inject: [
            MIGRATION_API_SERVICE,
        ],

        data(): SwagMigrationLogDownloadMixinData {
            return {
                isLogDownloading: false,
            };
        },

        methods: {
            async onDownloadLogs(runId: string): Promise<void> {
                this.isLogDownloading = true;

                try {
                    const blob = await this.migrationApiService.downloadLogsOfRun(runId);
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');

                    link.href = url;
                    link.download = `migrationRunLog-${runId}.txt`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                } catch {
                    this.createNotificationError({
                        message: this.$tc('swag-migration.index.error-resolution.errors.downloadLogsFailed'),
                    });
                } finally {
                    this.isLogDownloading = false;
                }
            },
        },
    }),
);
