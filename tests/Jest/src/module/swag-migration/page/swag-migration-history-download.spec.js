import { createPinia } from 'pinia';
import { shallowMount } from '@vue/test-utils';
import SwagMigrationHistory from 'SwagMigrationAssistant/module/swag-migration/page/swag-migration-history';
import SwagMigrationHistoryDetailErrors from 'SwagMigrationAssistant/module/swag-migration/page/swag-migration-history-detail-errors';

Shopware.Component.register('swag-migration-history', () => SwagMigrationHistory);
Shopware.Component.register('swag-migration-history-detail-errors', () => SwagMigrationHistoryDetailErrors);

describe.each([
    [
        'swag-migration-history',
        'swag-migration.history.contextMenu.downloadLogs',
    ],
    [
        'swag-migration-history-detail-errors',
        'swag-migration.history.detailPage.logDownload',
    ],
])('%s log download', (componentName, downloadLabel) => {
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
        wrapper?.unmount();
        jest.restoreAllMocks();
        window.URL.createObjectURL = originalCreateObjectURL;
        window.URL.revokeObjectURL = originalRevokeObjectURL;
    });

    async function createWrapper() {
        const runs = Object.assign([{ id: 'run-id' }], { total: 1 });

        wrapper = shallowMount(await Shopware.Component.build(componentName), {
            props: componentName === 'swag-migration-history-detail-errors' ? { migrationRun: runs[0] } : {},
            global: {
                plugins: [createPinia()],
                provide: {
                    searchRankingService: {},
                    migrationApiService: {
                        downloadLogsOfRun,
                        getGroupedLogsOfRun: jest.fn().mockResolvedValue({ items: [], total: 0 }),
                    },
                    repositoryFactory: {
                        create: () => ({ search: jest.fn().mockResolvedValue(runs) }),
                    },
                },
                mocks: {
                    $route: {
                        name: 'swag.migration.index.history',
                        query: { page: 1, limit: 25 },
                    },
                },
                stubs: {
                    'mt-card': { template: '<div><slot name="grid" /></div>' },
                    'sw-data-grid': {
                        props: ['dataSource'],
                        template: '<div><slot name="actions" :item="dataSource[0]" /></div>',
                    },
                    'sw-context-menu-item': { template: '<button><slot /></button>' },
                    'mt-link': { template: '<a href="#"><slot /></a>' },
                    'router-view': true,
                },
            },
        });

        await flushPromises();
    }

    it('downloads the selected run through the API service', async () => {
        const blob = new Blob(['migration log'], { type: 'text/plain' });
        downloadLogsOfRun.mockResolvedValue(blob);
        await createWrapper();

        const downloadControl = wrapper.findAll('button, a').find((control) => control.text() === downloadLabel);
        await downloadControl.trigger('click');
        await flushPromises();

        expect(downloadLogsOfRun).toHaveBeenCalledWith('run-id');
        expect(window.URL.createObjectURL).toHaveBeenCalledWith(blob);
        expect(clickSpy).toHaveBeenCalledTimes(1);
        const link = clickSpy.mock.instances[0];
        expect(link.href).toBe('blob:migration-log');
        expect(link.download).toBe('migrationRunLog-run-id.txt');
        expect(document.body.contains(link)).toBe(false);
        expect(window.URL.revokeObjectURL).toHaveBeenCalledWith('blob:migration-log');
        expect(wrapper.find('form').exists()).toBe(false);
    });

    it('shows an error notification when the download fails', async () => {
        downloadLogsOfRun.mockRejectedValue(new Error('Download failed'));
        await createWrapper();

        const downloadControl = wrapper.findAll('button, a').find((control) => control.text() === downloadLabel);
        await downloadControl.trigger('click');
        await flushPromises();

        expect(downloadLogsOfRun).toHaveBeenCalledWith('run-id');
        expect(clickSpy).not.toHaveBeenCalled();
        expect(Object.values(Shopware.Store.get('notification').notifications)).toEqual([
            expect.objectContaining({
                message: 'swag-migration.index.error-resolution.errors.downloadLogsFailed',
            }),
        ]);
    });
});
