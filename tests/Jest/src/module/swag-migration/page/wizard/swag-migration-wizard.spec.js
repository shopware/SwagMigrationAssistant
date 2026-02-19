import { config, mount } from '@vue/test-utils';
import { MtUrlField } from '@shopware-ag/meteor-component-library';
import SwagMigrationBase from 'SwagMigrationAssistant/module/swag-migration/page/swag-migration-base';
import { createRouter, createWebHashHistory } from 'vue-router';
import 'SwagMigrationAssistant/module/swag-migration/mixin/swag-wizard.mixin';
import SwagMigrationWizard from 'SwagMigrationAssistant/module/swag-migration/page/wizard/swag-migration-wizard';

import SwagMigrationWizardPageConnectionCreate from 'SwagMigrationAssistant/module/swag-migration/page/wizard/swag-migration-wizard-page-connection-create';
import SwagMigrationWizardPageCredentials from 'SwagMigrationAssistant/module/swag-migration/page/wizard/swag-migration-wizard-page-credentials';
import SwagMigrationWizardPageCredentialsSuccess from 'SwagMigrationAssistant/module/swag-migration/page/wizard/swag-migration-wizard-page-credentials-success';
import SwagMigrationWizardPageCredentialsError from 'SwagMigrationAssistant/module/swag-migration/page/wizard/swag-migration-wizard-page-credentials-error';
import SwagMigrationProfileShopwareApiCredentialForm from 'SwagMigrationAssistant/module/swag-migration/profile/shopware/api/swag-migration-profile-shopware-api-credential-form';
import { MIGRATION_STEP } from 'SwagMigrationAssistant/core/service/api/swag-migration.api.service';

Shopware.Component.register('swag-migration-base', () => SwagMigrationBase);
Shopware.Component.register('swag-migration-wizard', SwagMigrationWizard);
Shopware.Component.register('swag-migration-wizard-page-connection-create', SwagMigrationWizardPageConnectionCreate);
Shopware.Component.register('swag-migration-wizard-page-credentials', SwagMigrationWizardPageCredentials);
Shopware.Component.register('swag-migration-wizard-page-credentials-success', SwagMigrationWizardPageCredentialsSuccess);
Shopware.Component.register('swag-migration-wizard-page-credentials-error', SwagMigrationWizardPageCredentialsError);
Shopware.Component.register(
    'swag-migration-profile-shopware-api-credential-form',
    SwagMigrationProfileShopwareApiCredentialForm,
);
Shopware.Component.extend(
    'swag-migration-profile-shopware55-api-credential-form',
    'swag-migration-profile-shopware-api-credential-form',
    {},
);

const defaultMigrationState = {
    step: MIGRATION_STEP.IDLE,
};

const connectionName = 'my connection';
    const connectionId = 'connection-id';
    const apiKey = 'dummy40charactersApiKeyMustBeFilledxxxxx';
    const apiUser = 'user';
    const profileShopware55 = 'shopware55';
    const gatewayApi = 'api';
    const endpoint = 'https://shopware.com';

const environementInformationSuccessMock = {
    sourceSystemName: 'Shopware',
    sourceSystemVersion: '5.7',
    sourceSystemDomain: 'sw5.local',
    totals: { category: { entityName: 'category', total: 10 } },
    additionalData: [],
    requestStatus: { code: '', message: 'No error.', isWarning: false },
    migrationDisabled: false,
    displayWarnings: [],
    targetSystemCurrency: 'EUR',
    sourceSystemCurrency: 'EUR',
    sourceSystemLocale: 'de-DE',
    targetSystemLocale: 'en-GB',
    fingerprint: 'sw5-fingerprint',
};

const createNewConnectionErrorResponseMock = {
    response: {
        data: {
            errors: [
                {
                    status: '400',
                    code: 'ERROR_CODE',
                    detail: 'Detailed error message',
                    title: 'Error Title',
                },
            ],
        },
    },
};

const migrationApiServiceMock = {
    getState: jest.fn(() => Promise.resolve(defaultMigrationState)),
    // can be empty for basic tests
    // swag-migration-wizard-page-connection-create/index.ts::selectDefaultProfile is not reading these responses
    getProfiles: jest.fn(() => Promise.resolve([])),
    getGateways: jest.fn(() => Promise.resolve([])),
    getEnvironmentInformation: jest.fn(() => Promise.resolve(environementInformationSuccessMock)),
    createNewConnection: jest.fn(() => Promise.resolve(environementInformationSuccessMock)),
};

const connectionRepositoryMock = {
    search: jest.fn(() => Promise.resolve([])),
    create: jest.fn(() => ({
        id: connectionId,
        name: connectionName,
        profileName: profileShopware55,
        gatewayName: gatewayApi,
        credentialFields: {},
    })),
    save: jest.fn(() => Promise.resolve()),
};

const generalSettingsRepositoryMock = {
    search: jest.fn(() =>
        Promise.resolve({
            length: 1,
            first: () => ({
                id: 'selected-connection-setting-id',
                selectedConnectionId: 'any-id',
            }),
        }),
    ),
    save: jest.fn(() => Promise.resolve()),
};

const repositoryFactoryMock = {
    create: (repositoryName) => {
        if (repositoryName === 'swag_migration_connection') {
            return connectionRepositoryMock;
        }

        if (repositoryName === 'swag_migration_general_setting') {
            return generalSettingsRepositoryMock;
        }

        return null;
    },
};

const router = createRouter({
    routes: [
        { path: '/', component: { template: '<div></div>' } },
        {
            path: '/connection/create',
            name: 'swag.migration.wizard.connectionCreate',
            component: () => Shopware.Component.build('swag-migration-wizard-page-connection-create'),
        },
        {
            path: '/credentials',
            name: 'swag.migration.wizard.credentials',
            component: () => Shopware.Component.build('swag-migration-wizard-page-credentials'),
        },
        {
            path: '/credentials/success',
            name: 'swag.migration.wizard.credentialsSuccess',
            component: () => Shopware.Component.build('swag-migration-wizard-page-credentials-success'),
        },
        {
            path: '/credentials/error',
            name: 'swag.migration.wizard.credentialsError',
            component: () => Shopware.Component.build('swag-migration-wizard-page-credentials-error'),
        },
    ],
    history: createWebHashHistory(),
});

// when update() in the mixin is triggered, router.push() may not finished in the test
// update() triggers matchCurrentRoute() to update this.currentRoute based on the current router route
// if router.push is not finished, this.currentRoute will not be updated
// so, if router route name does not match with this->currentRoute
// trigger matchCurrentRoute() execution to update this.currentRoute
async function syncCurrentRoute(wrapper, routerCurrentRouteName) {
    if (wrapper.vm.currentRoute.name !== routerCurrentRouteName) {
        wrapper.vm.matchCurrentRoute(false);
        await flushPromises();
    }
}

async function createWrapper() {
    // delete global $router and $routes mocks
    delete config.global.mocks.$router;
    delete config.global.mocks.$route;

    return mount(await Shopware.Component.build('swag-migration-wizard'), {
        global: {
            stubs: {
                'sw-modal': {
                    template: `<div class="sw-modal">
                        <div class="sw-modal__body"><slot /></div>
                        <div class="sw-modal__footer"><slot name="modal-footer" /></div>
                    </div>`,
                },
                'swag-migration-profile-shopware55-api-credential-form': await Shopware.Component.build(
                    'swag-migration-profile-shopware55-api-credential-form',
                ),
                'sw-single-select': await wrapTestComponent('sw-single-select'),
                'sw-select-result-list': await wrapTestComponent('sw-select-result-list'),
                'sw-select-result': await wrapTestComponent('sw-select-result'),
                'sw-select-base': await wrapTestComponent('sw-select-base'),
                'sw-block-field': await wrapTestComponent('sw-block-field'),
                'sw-base-field': await wrapTestComponent('sw-base-field'),
                'mt-banner': true,
                'mt-icon': true,
                'router-link': true,
                'sw-loader': true,
                'sw-highlight-text': true,
                'sw-inheritance-switch': true,
                'sw-ai-copilot-badge': true,
                'sw-help-text': true,
                'sw-field-error': true,
                'mt-url-field': MtUrlField,
                'i18n-t': {
                    template: '<div class="i18n-stub"><slot></slot></div>',
                },
            },
            plugins: [router],
            provide: {
                migrationApiService: migrationApiServiceMock,
                repositoryFactory: repositoryFactoryMock,
                shortcutService: {
                    startEventListener: () => {},
                    stopEventListener: () => {},
                },
            },
        },
    });
}

describe('src/module/swag-migration/page/wizard/swag-migration-wizard', () => {
    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('create connection page', () => {
        it('should render "create profile" form', async () => {
            router.push({ name: 'swag.migration.wizard.connectionCreate' });
            const wrapper = await createWrapper();
            await flushPromises();

            const fieldName = wrapper.find(
                'input[aria-label="swag-migration.wizard.pages.connectionCreate.connectionLabel"]',
            );
            expect(fieldName.exists()).toBe(true);

            const profile = wrapper.find('input[aria-label="swag-migration.wizard.pages.connectionCreate.profileLabel"]');
            expect(profile.exists()).toBe(true);

            const gateway = wrapper.find('input[aria-label="swag-migration.wizard.pages.connectionCreate.gatewayLabel"]');
            expect(gateway.exists()).toBe(true);

            const buttons = wrapper.findAll('.swag-migration-wizard__footer button');
            expect(buttons).toHaveLength(2);
        });

        it('should have "next" button disabled when form is not filled out', async () => {
            router.push({ name: 'swag.migration.wizard.connectionCreate' });
            const wrapper = await createWrapper();
            await flushPromises();

            const nextButton = wrapper.find('.swag-migration-wizard__footer button.mt-button--primary');
            expect(nextButton.exists()).toBe(true);
            expect(nextButton.attributes('disabled')).toBeDefined();
        });

        it('should enable "next" button when all required fields are filled out', async () => {
            router.push({ name: 'swag.migration.wizard.connectionCreate' });
            const wrapper = await createWrapper();
            await flushPromises();

            // profile page
            // use default profile which is already set in
            // swag-migration-wizard-page-connection-create/index.ts::selectDefaultProfile
            await wrapper
                .find('input[aria-label="swag-migration.wizard.pages.connectionCreate.connectionLabel"]')
                .setValue(connectionName);

            await flushPromises();

            const nextButton = wrapper.find('.swag-migration-wizard__footer button.mt-button--primary');
            expect(nextButton.attributes('disabled')).toBeUndefined();
        });
    });

    describe('navigation from "create connection" to "credentials" page', () => {
        it('should navigate to credentials page when form data is valid', async () => {
            router.push({ name: 'swag.migration.wizard.connectionCreate' });
            const wrapper = await createWrapper();
            await flushPromises();

            // profile page
            // use default profile which is already set in
            // swag-migration-wizard-page-connection-create/index.ts::selectDefaultProfile
            await wrapper
                .find('input[aria-label="swag-migration.wizard.pages.connectionCreate.connectionLabel"]')
                .setValue(connectionName);

            await flushPromises();

            await wrapper.find('.swag-migration-wizard__footer button.mt-button--primary').trigger('click');

            await flushPromises();

            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentials');
            const wrapperCredentialsPage = wrapper.find('.swag-migration-wizard-page-credentials');
            expect(wrapperCredentialsPage.exists()).toBe(true);
            expect(connectionRepositoryMock.save).not.toHaveBeenCalled();
        });

        it('should not navigate to credentials page when connection name already exits', async () => {
            router.push({ name: 'swag.migration.wizard.connectionCreate' });
            const wrapper = await createWrapper();
            await flushPromises();

            // profile page
            // use default profile which is already set in
            // swag-migration-wizard-page-connection-create/index.ts::selectDefaultProfile
            await wrapper
                .find('input[aria-label="swag-migration.wizard.pages.connectionCreate.connectionLabel"]')
                .setValue(connectionName);

            await flushPromises();

            connectionRepositoryMock.search.mockReturnValueOnce(
                Promise.resolve([
                    {
                        id: 'existing-connection-id',
                        name: 'my connection',
                    },
                ]),
            );

            await wrapper.find('.swag-migration-wizard__footer button.mt-button--primary').trigger('click');

            await flushPromises();

            const connectionNameErrorAfter = wrapper.find('.mt-field__error');
            expect(connectionNameErrorAfter.exists()).toBe(true);

            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.connectionCreate');
            const wrapperCredentialsPage = wrapper.find('.swag-migration-wizard-page-credentials');
            expect(wrapperCredentialsPage.exists()).toBe(false);
            const wrapperProfilPage = wrapper.find('.swag-migration-wizard-page-create-profile');
            expect(wrapperProfilPage.exists()).toBe(true);
        });
    });

    describe('render credentials page', () => {
        it('should render credentials form', async () => {
            router.push({ name: 'swag.migration.wizard.connectionCreate' });
            const wrapper = await createWrapper();
            await flushPromises();

            // profile page
            // use default profile which is already set in
            // swag-migration-wizard-page-connection-create/index.ts::selectDefaultProfile
            await wrapper
                .find('input[aria-label="swag-migration.wizard.pages.connectionCreate.connectionLabel"]')
                .setValue(connectionName);

            await flushPromises();

            await wrapper.find('.swag-migration-wizard__footer button.mt-button--primary').trigger('click');

            await flushPromises();

            // credentials page
            const fieldApiKey = wrapper.find('input[name="sw-field--apiKey"]');
            expect(fieldApiKey.exists()).toBe(true);

            const fieldApiUser = wrapper.find('input[name="sw-field--apiUser"]');
            expect(fieldApiUser.exists()).toBe(true);

            const fieldUrl = wrapper.find('input.mt-url-field__input');
            expect(fieldUrl.exists()).toBe(true);

            const buttons = wrapper.findAll('.swag-migration-wizard__footer button');
            expect(buttons).toHaveLength(2);
        });

        it('should have "next" button disabled when form is not filled out', async () => {
            router.push({ name: 'swag.migration.wizard.connectionCreate' });
            const wrapper = await createWrapper();
            await flushPromises();

            // profile page
            // use default profile which is already set in
            // swag-migration-wizard-page-connection-create/index.ts::selectDefaultProfile
            await wrapper
                .find('input[aria-label="swag-migration.wizard.pages.connectionCreate.connectionLabel"]')
                .setValue(connectionName);

            await flushPromises();

            await wrapper.find('.swag-migration-wizard__footer button.mt-button--primary').trigger('click');

            await flushPromises();

            // credentials page
            const createConnectionButton = wrapper.find('.swag-migration-wizard__footer button.mt-button--primary');
            expect(createConnectionButton.exists()).toBe(true);
            expect(createConnectionButton.attributes('disabled')).toBeDefined();
        });
    });

    describe('navigate from "credentials" to result page (error or success)', () => {
        it('should navigate to success page when connection was successfully created', async () => {
            router.push({ name: 'swag.migration.wizard.connectionCreate' });
            const wrapper = await createWrapper();
            await flushPromises();

            // profile page
            // use default profile which is already set in
            // swag-migration-wizard-page-connection-create/index.ts::selectDefaultProfile
            await wrapper
                .find('input[aria-label="swag-migration.wizard.pages.connectionCreate.connectionLabel"]')
                .setValue(connectionName);

            await flushPromises();

            await wrapper.find('.swag-migration-wizard__footer button.mt-button--primary').trigger('click');

            await flushPromises();

            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentials');

            await syncCurrentRoute(wrapper, router.currentRoute.value.name);

            // credentials page
            await wrapper.find('input[name="sw-field--apiKey"]').setValue(apiKey);
            await wrapper.find('input[name="sw-field--apiUser"]').setValue(apiUser);
            await wrapper.find('input.mt-url-field__input').setValue(endpoint);

            await flushPromises();

            const createConnectionButton = wrapper.find('.swag-migration-wizard__footer button.mt-button--primary');
            expect(createConnectionButton.attributes('disabled')).toBeUndefined();
            await createConnectionButton.trigger('click');

            await flushPromises();

            expect(migrationApiServiceMock.createNewConnection).toHaveBeenCalledWith(
                connectionId,
                connectionName,
                profileShopware55,
                gatewayApi,
                {
                    apiKey: apiKey,
                    apiUser: apiUser,
                    endpoint: endpoint,
                },
            );

            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentialsSuccess');

            await syncCurrentRoute(wrapper, router.currentRoute.value.name);

            // success page
            const successWrapper = wrapper.find('.swag-migration-wizard-page-credentials-success');
            expect(successWrapper.exists()).toBe(true);

            const successButtons = wrapper.findAll('.swag-migration-wizard__footer button');
            expect(successButtons).toHaveLength(2);

            const successSecondaryButton = wrapper.find('button.mt-button--secondary');
            expect(successSecondaryButton.exists()).toBe(true);
            expect(successSecondaryButton.attributes('style')).toContain('display: none');

            const successPrimaryButton = wrapper.find('button.mt-button--primary');
            expect(successPrimaryButton.exists()).toBe(true);
            expect(successPrimaryButton.attributes('disabled')).toBeUndefined();
        });

        it('should navigate to error page when create connection fails', async () => {
            migrationApiServiceMock.createNewConnection.mockRejectedValueOnce(createNewConnectionErrorResponseMock);
            router.push({ name: 'swag.migration.wizard.connectionCreate' });
            const wrapper = await createWrapper();
            await flushPromises();

            // profile page
            // use default profile which is already set in
            // swag-migration-wizard-page-connection-create/index.ts::selectDefaultProfile
            await wrapper
                .find('input[aria-label="swag-migration.wizard.pages.connectionCreate.connectionLabel"]')
                .setValue(connectionName);

            await flushPromises();

            await wrapper.find('.swag-migration-wizard__footer button.mt-button--primary').trigger('click');

            await flushPromises();

            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentials');

            await syncCurrentRoute(wrapper, router.currentRoute.value.name);

            // credentials page
            await wrapper.find('input[name="sw-field--apiKey"]').setValue(apiKey);
            await wrapper.find('input[name="sw-field--apiUser"]').setValue(apiUser);
            await wrapper.find('input.mt-url-field__input').setValue(endpoint);

            await flushPromises();

            await wrapper.find('.swag-migration-wizard__footer button.mt-button--primary').trigger('click');

            await flushPromises();

            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentialsError');

            await syncCurrentRoute(wrapper, router.currentRoute.value.name);

            // error page
            const errorWrapper = wrapper.find('.swag-migration-wizard-page-credentials-error');
            expect(errorWrapper.exists()).toBe(true);

            const errorButtons = wrapper.findAll('.swag-migration-wizard__footer button');
            expect(errorButtons).toHaveLength(2);

            const errorSecondaryButton = wrapper.find('button.mt-button--secondary');
            expect(errorSecondaryButton.exists()).toBe(true);
            expect(errorSecondaryButton.attributes('disabled')).toBeUndefined();

            const errorPrimaryButton = wrapper.find('button.mt-button--primary');
            expect(errorPrimaryButton.exists()).toBe(true);
            expect(errorPrimaryButton.attributes('disabled')).toBeUndefined();
        });
    });
});
