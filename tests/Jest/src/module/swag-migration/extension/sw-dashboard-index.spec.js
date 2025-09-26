/**
 * @sw-package fundamentals@after-sales
 */
import { mount } from '@vue/test-utils';
import swDashboardIndex from 'src/module/sw-dashboard/page/sw-dashboard-index';
import swMigrationDashboardIndex from 'SwagMigrationAssistant/module/swag-migration/extension/sw-dashboard-index';

Shopware.Component.register('sw-dashboard-index', swDashboardIndex);
Shopware.Component.override('sw-dashboard-index', swMigrationDashboardIndex);

const aclMock = {
    isAdmin: jest.fn(() => true),
};

async function createWrapper() {
    return mount(await Shopware.Component.build('sw-dashboard-index'), {
        global: {
            stubs: {
                'sw-page': await wrapTestComponent('sw-page'),
                'sw-card-view': await wrapTestComponent('sw-card-view'),
                'swag-migration-dashboard-card': true,
                'sw-settings-services-dashboard-banner': true,
                'sw-usage-data-consent-banner': true,
                'sw-extension-component-section': true,
                'sw-external-link': true,
                'sw-dashboard-statistics': true,
                'sw-search-bar': true,
                'sw-app-topbar-button': true,
                'sw-notification-center': true,
                'sw-help-center-v2': true,
                'sw-app-topbar-sidebar': true,
                'sw-app-actions': true,
                'router-link': true,
                'sw-error-summary': true,
            },
            provide: {
                acl: aclMock,
            },
            mocks: {
                $route: {
                    meta: {
                        $module: {
                            name: 'sw-dashboard',
                        },
                    },
                },
            },
        },
    });
}

describe('src/module/swag-migration/extension/swag-dashboard-index', () => {
    afterEach(() => {
        jest.clearAllMocks();
    });

    it.each([
        { name: 'admin', isAdmin: true, expected: true },
        { name: 'editor', isAdmin: false, expected: false },
    ])('should render migration dashboard card if user is admin: $name', async ({ isAdmin, expected }) => {
        aclMock.isAdmin.mockReturnValue(isAdmin);

        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('swag-migration-dashboard-card-stub').exists()).toBe(expected);
    });
});
