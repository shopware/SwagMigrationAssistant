import { createPinia } from 'pinia';
import { mount } from '@vue/test-utils';
import 'SwagMigrationAssistant/module/swag-migration/mixin/swag-migration-log-download.mixin';
import SwagMigrationHistoryDetailErrors from 'SwagMigrationAssistant/module/swag-migration/page/swag-migration-history-detail-errors';

Shopware.Component.register('swag-migration-history-detail-errors', () => SwagMigrationHistoryDetailErrors);

describe('module/swag-migration/page/swag-migration-history-detail-errors', () => {
    let wrapper;
    let downloadLogsOfRun;
    let clickSpy;
    const originalCreateObjectURL = window.URL.createObjectURL;
    const originalRevokeObjectURL = window.URL.revokeObjectURL;

    beforeEach(() => {
        downloadLogsOfRun = jest.fn();
        window.URL.createObjectURL = jest.fn(() => 'blob:migration-log');
        window.URL.revokeObjectURL = jest.fn();
        clickSpy = jest.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
    });

    afterEach(() => {
        jest.restoreAllMocks();
        window.URL.createObjectURL = originalCreateObjectURL;
        window.URL.revokeObjectURL = originalRevokeObjectURL;
    });

    async function createWrapper() {
        wrapper = mount(await Shopware.Component.build('swag-migration-history-detail-errors'), {
            props: {
                migrationRun: {
                    id: 'run-id',
                    step: 'finished',
                },
            },
            global: {
                plugins: [createPinia()],
                provide: {
                    searchRankingService: {},
                    migrationApiService: {
                        downloadLogsOfRun,
                        getGroupedLogsOfRun: jest.fn().mockResolvedValue({ items: [], total: 0 }),
                    },
                },
                mocks: {
                    $route: {
                        name: 'swag.migration.index.history.detail',
                        query: { page: 1, limit: 25 },
                    },
                },
                stubs: {
                    'sw-data-grid': true,
                    'sw-pagination': true,
                },
            },
        });

        await flushPromises();
    }

    function findDownloadLink() {
        return wrapper.find('.swag-migration-history-detail-errors__description a');
    }

    it('downloads the run through the API service', async () => {
        const blob = new Blob(['migration log'], { type: 'text/plain' });
        downloadLogsOfRun.mockResolvedValue(blob);
        await createWrapper();

        await findDownloadLink().trigger('click');
        await flushPromises();

        expect(downloadLogsOfRun).toHaveBeenCalledWith('run-id');
        expect(window.URL.createObjectURL).toHaveBeenCalledWith(blob);
        expect(clickSpy).toHaveBeenCalledTimes(1);
        const link = clickSpy.mock.instances[0];
        expect(link.href).toBe('blob:migration-log');
        expect(link.download).toBe('migrationRunLog-run-id.txt');
        expect(document.body.contains(link)).toBe(false);
        expect(window.URL.revokeObjectURL).toHaveBeenCalledWith('blob:migration-log');
    });

    it('shows an error notification when the download fails', async () => {
        downloadLogsOfRun.mockRejectedValue(new Error('Download failed'));
        await createWrapper();

        await findDownloadLink().trigger('click');
        await flushPromises();

        expect(downloadLogsOfRun).toHaveBeenCalledWith('run-id');
        expect(clickSpy).not.toHaveBeenCalled();
        expect(Object.values(Shopware.Store.get('notification').notifications)).toEqual([
            expect.objectContaining({
                message: 'swag-migration.index.error-resolution.errors.downloadLogsFailed',
            }),
        ]);
    });

    it('disables the download link while the download is in progress', async () => {
        let resolveDownload;
        downloadLogsOfRun.mockReturnValue(
            new Promise((resolve) => {
                resolveDownload = resolve;
            }),
        );
        await createWrapper();

        expect(findDownloadLink().attributes('aria-disabled')).toBe('false');

        await findDownloadLink().trigger('click');
        await flushPromises();

        expect(findDownloadLink().attributes('aria-disabled')).toBe('true');

        resolveDownload(new Blob(['migration log'], { type: 'text/plain' }));
        await flushPromises();

        expect(findDownloadLink().attributes('aria-disabled')).toBe('false');
    });
});
