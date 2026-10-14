import { createPinia } from 'pinia';
import { DOMWrapper, mount } from '@vue/test-utils';
import 'SwagMigrationAssistant/module/swag-migration/mixin/swag-migration-log-download.mixin';
import SwagMigrationHistory from 'SwagMigrationAssistant/module/swag-migration/page/swag-migration-history';

Shopware.Component.register('swag-migration-history', () => SwagMigrationHistory);

const DOWNLOAD_LABEL = 'swag-migration.history.contextMenu.downloadLogs';

describe('module/swag-migration/page/swag-migration-history', () => {
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
        const runs = Object.assign(
            [
                {
                    id: 'run-id',
                    step: 'finished',
                    createdAt: '2026-10-13T00:00:00.000+00:00',
                    connection: {
                        name: 'Connection name',
                        profileName: 'Profile name',
                        gatewayName: 'Gateway name',
                    },
                    environmentInformation: {
                        sourceSystemDomain: 'example.com',
                        sourceSystemName: 'Shopware',
                        sourceSystemVersion: '6.5.0.0',
                    },
                },
            ],
            { total: 1 },
        );

        wrapper = mount(await Shopware.Component.build('swag-migration-history'), {
            global: {
                plugins: [createPinia()],
                provide: {
                    searchRankingService: {},
                    migrationApiService: {
                        downloadLogsOfRun,
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
                    'sw-data-grid': await wrapTestComponent('sw-data-grid'),
                    'sw-context-button': await wrapTestComponent('sw-context-button'),
                    'sw-context-menu': await wrapTestComponent('sw-context-menu'),
                    'sw-context-menu-item': await wrapTestComponent('sw-context-menu-item'),
                    'sw-popover': await wrapTestComponent('sw-popover'),
                    'sw-popover-deprecated': await wrapTestComponent('sw-popover-deprecated'),
                    'sw-provide': await wrapTestComponent('sw-provide'),
                    'sw-pagination': await wrapTestComponent('sw-pagination'),
                    'sw-data-grid-column-boolean': true,
                    'sw-data-grid-inline-edit': true,
                    'sw-data-grid-settings': true,
                    'sw-data-grid-skeleton': true,
                    'router-link': true,
                    'router-view': true,
                },
            },
        });

        await flushPromises();
    }

    function findDownloadMenuItem() {
        return new DOMWrapper(document.body).findAll('.sw-context-menu-item').find((item) => item.text() === DOWNLOAD_LABEL);
    }

    async function openDownloadMenuItem() {
        const menuItem = findDownloadMenuItem();

        if (menuItem) {
            return menuItem;
        }

        await wrapper.find('.sw-data-grid__actions-menu').trigger('click');
        await flushPromises();

        return findDownloadMenuItem();
    }

    it('downloads the selected run through the API service', async () => {
        const blob = new Blob(['migration log'], { type: 'text/plain' });
        downloadLogsOfRun.mockResolvedValue(blob);
        await createWrapper();

        const menuItem = await openDownloadMenuItem();
        await menuItem.trigger('click');
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

        const menuItem = await openDownloadMenuItem();
        await menuItem.trigger('click');
        await flushPromises();

        expect(downloadLogsOfRun).toHaveBeenCalledWith('run-id');
        expect(clickSpy).not.toHaveBeenCalled();
        expect(Object.values(Shopware.Store.get('notification').notifications)).toEqual([
            expect.objectContaining({
                message: 'swag-migration.index.error-resolution.errors.downloadLogsFailed',
            }),
        ]);
    });

    it('disables the download menu item while the download is in progress', async () => {
        let resolveDownload;
        downloadLogsOfRun.mockReturnValue(
            new Promise((resolve) => {
                resolveDownload = resolve;
            }),
        );
        await createWrapper();

        const menuItem = await openDownloadMenuItem();
        expect(menuItem.classes()).not.toContain('is--disabled');

        await menuItem.trigger('click');
        await flushPromises();

        expect((await openDownloadMenuItem()).classes()).toContain('is--disabled');

        resolveDownload(new Blob(['migration log'], { type: 'text/plain' }));
        await flushPromises();

        expect((await openDownloadMenuItem()).classes()).not.toContain('is--disabled');
    });
});
