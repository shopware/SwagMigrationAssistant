/**
 * @sw-package after-sales
 */
import { createPinia } from 'pinia';
import { mount } from '@vue/test-utils';
import SwagMigrationBase from 'SwagMigrationAssistant/module/swag-migration/page/swag-migration-base';
import SwagMigrationProcessScreen, {
    MIGRATION_STATE_POLLING_INTERVAL,
    MIGRATION_STEP_DISPLAY_INDEX,
    UI_COMPONENT_INDEX,
} from 'SwagMigrationAssistant/module/swag-migration/page/swag-migration-process-screen';
import { MIGRATION_STEP } from 'SwagMigrationAssistant/core/service/api/swag-migration.api.service';

Shopware.Component.register('swag-migration-base', () => SwagMigrationBase);
Shopware.Component.extend('swag-migration-process-screen', 'swag-migration-base', () => SwagMigrationProcessScreen);

const defaultMigrationState = {
    step: MIGRATION_STEP.FETCHING,
};

const repositoryMock = {
    search: jest.fn(() => Promise.resolve({
        first: () => ({
            id: '1',
            selectedConnectionId: 'connection-id',
        }),
    })),
};

const migrationApiServiceMock = {
    getState: jest.fn(() => Promise.resolve(defaultMigrationState)),
    checkConnection: jest.fn(() => Promise.resolve({})),
    getDataSelection: jest.fn(() => Promise.resolve([])),
};

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-migration-process-screen'), {
        global: {
            plugins: [createPinia()],
            stubs: {
                'sw-card-view': await wrapTestComponent('sw-card-view'),
                'swag-migration-error-resolution-step': true,
                'swag-migration-loading-screen': true,
                'swag-migration-result-screen': true,
                'sw-error-summary': true,
                'sw-step-display': true,
                'sw-step-item': true,
                'sw-page': {
                    template: `
                    <div class="sw-page">
                        <slot name="smart-bar-actions"></slot>
                        <slot name="content"></slot>
                        <slot></slot>
                    </div>`,
                },
            },
            provide: {
                migrationApiService: migrationApiServiceMock,
                repositoryFactory: {
                    create: () => repositoryMock,
                },
            },
            mocks: {
                $route: {
                    query: {},
                },
            },
        },
    });
}

describe('src/module/swag-migration/page/swag-migration-process-screen', () => {
    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('constants', () => {
        it('should have a migration state polling rate interval constant', () => {
            expect(MIGRATION_STATE_POLLING_INTERVAL).toBe(1000);
        });

        it('should provide migration step order constant', () => {
            expect(Object.entries(MIGRATION_STEP_DISPLAY_INDEX).sort()).toStrictEqual([
                [
                    'aborting',
                    4,
                ],
                [
                    'apply-fixes',
                    1,
                ],
                [
                    'cleanup',
                    4,
                ],
                [
                    'fetching',
                    0,
                ],
                [
                    'idle',
                    0,
                ],
                [
                    'indexing',
                    5,
                ],
                [
                    'media-processing',
                    3,
                ],
                [
                    'waiting-for-approve',
                    6,
                ],
                [
                    'writing',
                    2,
                ],
            ]);
        });

        it('should provide migration step ui index constant', () => {
            expect(Object.entries(UI_COMPONENT_INDEX).sort()).toStrictEqual([
                [
                    'ERROR_RESOLUTION',
                    1,
                ],
                [
                    'LOADING_SCREEN',
                    0,
                ],
                [
                    'RESULT_SUCCESS',
                    2,
                ],
            ]);
        });
    });

    it('should visualize loading screen by default', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('swag-migration-loading-screen-stub').exists()).toBe(true);
    });

    describe('error-resolution', () => {
        it('should add meta info for error resolution step', async () => {
            migrationApiServiceMock.getState.mockReturnValueOnce(
                Promise.resolve({
                    step: MIGRATION_STEP.ERROR_RESOLUTION,
                }),
            );

            const wrapper = await createWrapper();
            await flushPromises();

            // no other way to trigger metaInfo computation, then calling it directly
            const metaInfo = wrapper.vm.$options.metaInfo.call(wrapper.vm);

            expect(metaInfo.title).toBe(
                'swag-migration.index.error-resolution.step.header.title | global.sw-admin-menu.textShopwareAdmin',
            );
        });

        it('should visualize error resolution step', async () => {
            migrationApiServiceMock.getState.mockReturnValueOnce(
                Promise.resolve({
                    step: MIGRATION_STEP.ERROR_RESOLUTION,
                }),
            );

            const wrapper = await createWrapper();
            await flushPromises();

            expect(wrapper.find('swag-migration-error-resolution-step-stub').exists()).toBe(true);
        });
    });
});
