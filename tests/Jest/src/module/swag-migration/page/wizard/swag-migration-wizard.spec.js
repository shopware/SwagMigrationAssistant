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

const defaultMigrationState = { step: MIGRATION_STEP.IDLE };
const connectionName = 'my connection';
const connectionId = 'connection-id';
const apiKey = 'dummy40charactersApiKeyMustBeFilledxxxxx';
const apiUser = 'user';
const profileShopware55 = 'shopware55';
const gatewayApi = 'api';
const endpoint = 'https://shopware.com';
const primaryButtonSelector = '.swag-migration-wizard__footer button.mt-button--primary';
const connectionNameSelector = 'input[aria-label="swag-migration.wizard.pages.connectionCreate.connectionLabel"]';
const storedConnection = {
    id: connectionId,
    name: connectionName,
    profileName: profileShopware55,
    gatewayName: gatewayApi,
};

const environmentInformationSuccessMock = {
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

const apiErrorResponseMock = {
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
    getEnvironmentInformation: jest.fn(() => Promise.resolve(environmentInformationSuccessMock)),
    getConnectionCredentials: jest.fn(() => Promise.resolve({ apiKey, apiUser, endpoint })),
    checkConnection: jest.fn(() => Promise.resolve(environmentInformationSuccessMock)),
    createNewConnection: jest.fn(() => Promise.resolve(environmentInformationSuccessMock)),
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

const routes = [
    { path: '/', component: { template: '<div></div>' } },
    {
        path: '/profile/installation',
        name: 'swag.migration.wizard.profileInstallation',
        component: { template: '<div class="swag-migration-wizard-page-profile-installation"></div>' },
    },
    {
        path: '/connection/create',
        name: 'swag.migration.wizard.connectionCreate',
        component: () => Shopware.Component.build('swag-migration-wizard-page-connection-create'),
    },
    {
        path: '/connection/select',
        name: 'swag.migration.wizard.connectionSelect',
        component: {
            name: 'swag-migration-wizard-page-connection-select',
            template: '<div class="swag-migration-wizard-page-select-profile"></div>',
        },
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
];

const mountedWrappers = [];

async function createWrapper(initialRoute = 'swag.migration.wizard.connectionCreate') {
    // delete global $router and $routes mocks
    delete config.global.mocks.$router;
    delete config.global.mocks.$route;

    const router = createRouter({
        routes: [
            {
                path: '/wizard',
                component: await Shopware.Component.build('swag-migration-wizard'),
                children: routes,
            },
        ],
        history: createWebHashHistory(),
    });

    await router.push({ name: initialRoute });
    await router.isReady();
    await flushPromises();

    const wrapper = await mount(
        { template: '<router-view />' },
        {
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
        },
    );

    mountedWrappers.push(wrapper);
    expect(router.currentRoute.value.name).toBe(initialRoute);

    return { wrapper, router };
}

async function openCredentialsForm(wrapper) {
    await wrapper.find(connectionNameSelector).setValue(connectionName);
    await wrapper.find(primaryButtonSelector).trigger('click');
    await flushPromises();
}

async function fillCredentials(wrapper) {
    await wrapper.find('input[name="sw-field--apiKey"]').setValue(apiKey);
    await wrapper.find('input[name="sw-field--apiUser"]').setValue(apiUser);
    await wrapper.find('input.mt-url-field__input').setValue(endpoint);
    await flushPromises();
}

describe('src/module/swag-migration/page/wizard/swag-migration-wizard', () => {
    afterEach(() => {
        mountedWrappers.splice(0).forEach((wrapper) => wrapper.unmount());
        Shopware.Store.get('swagMigration').$reset();
        jest.clearAllMocks();
    });

    describe('load stored credentials', () => {
        beforeEach(() => {
            connectionRepositoryMock.search.mockResolvedValueOnce({
                length: 1,
                first: () => ({ ...storedConnection }),
            });
        });

        it('should keep loading until the editor endpoint populates the credential form', async () => {
            let resolveCredentials;
            migrationApiServiceMock.getConnectionCredentials.mockImplementationOnce(
                () =>
                    new Promise((resolve) => {
                        resolveCredentials = resolve;
                    }),
            );

            const { wrapper } = await createWrapper('swag.migration.wizard.credentials');
            await flushPromises();

            expect(migrationApiServiceMock.getConnectionCredentials).toHaveBeenCalledWith(connectionId);
            expect(wrapper.find('.swag-migration-wizard__placeholder').isVisible()).toBe(true);
            expect(wrapper.find(primaryButtonSelector).attributes('disabled')).toBeDefined();

            resolveCredentials({ apiKey, apiUser, endpoint });
            await flushPromises();

            expect(wrapper.find('.swag-migration-wizard__placeholder').attributes('style')).toContain('display: none;');
            expect(wrapper.find('input[name="sw-field--apiKey"]').element.value).toBe(apiKey);
            expect(wrapper.find('input[name="sw-field--apiUser"]').element.value).toBe(apiUser);
            expect(wrapper.findComponent(MtUrlField).props('modelValue')).toBe(endpoint);
            expect(wrapper.find(primaryButtonSelector).attributes('disabled')).toBeUndefined();
        });

        it.each([
            [
                'an API error',
                apiErrorResponseMock,
            ],
            [
                'a network error',
                new Error('Network Error'),
            ],
            [
                'a non-API response',
                { response: { data: 'Bad Gateway' } },
            ],
        ])('should allow editing the connection after %s', async (_description, error) => {
            let rejectCredentials;
            migrationApiServiceMock.getConnectionCredentials.mockImplementationOnce(
                () =>
                    new Promise((resolve, reject) => {
                        rejectCredentials = reject;
                    }),
            );

            const { wrapper, router } = await createWrapper('swag.migration.wizard.credentials');
            await flushPromises();

            rejectCredentials(error);
            await flushPromises();

            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentialsError');
            expect(wrapper.find('.swag-migration-wizard-page-credentials-error').isVisible()).toBe(true);
            expect(wrapper.find('.swag-migration-wizard__description').text()).toBe(
                'swag-migration.wizard.pages.credentials.error.undefinedErrorMsg',
            );
            expect(wrapper.find(primaryButtonSelector).attributes('disabled')).toBeUndefined();

            await wrapper.find(primaryButtonSelector).trigger('click');
            await flushPromises();

            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentials');
            expect(wrapper.find('input[name="sw-field--apiKey"]').element.value).toBe('');
        });
    });

    describe('select an existing connection', () => {
        it.each([
            [
                'an API error',
                apiErrorResponseMock,
            ],
            [
                'a network error',
                new Error('Network Error'),
            ],
        ])('should show %s when loading the selected credentials fails', async (_description, error) => {
            const selectedConnection = { ...storedConnection, id: 'selected-connection-id', name: 'selected connection' };
            connectionRepositoryMock.search.mockResolvedValueOnce({
                length: 1,
                first: () => ({ ...storedConnection }),
            });
            const { wrapper, router } = await createWrapper('swag.migration.wizard.credentials');
            await flushPromises();

            await router.push({ name: 'swag.migration.wizard.connectionSelect' });
            await flushPromises();

            const selectPage = wrapper.findComponent({ name: 'swag-migration-wizard-page-connection-select' });
            selectPage.vm.$emit('onConnectionSelected', selectedConnection);
            selectPage.vm.$emit('onChildRouteReadyChanged', true);
            await flushPromises();

            migrationApiServiceMock.checkConnection.mockClear();
            migrationApiServiceMock.getConnectionCredentials.mockRejectedValueOnce(error);
            await wrapper.find(primaryButtonSelector).trigger('click');
            await flushPromises();

            expect(migrationApiServiceMock.getConnectionCredentials).toHaveBeenLastCalledWith(selectedConnection.id);
            expect(migrationApiServiceMock.checkConnection).not.toHaveBeenCalled();
            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentialsError');
            expect(wrapper.find('.swag-migration-wizard-page-credentials-error').isVisible()).toBe(true);
        });
    });

    describe('create a connection', () => {
        it('should require a name and credentials before creating the connection', async () => {
            const { wrapper, router } = await createWrapper();
            await flushPromises();

            expect(
                wrapper.find('input[aria-label="swag-migration.wizard.pages.connectionCreate.profileLabel"]').exists(),
            ).toBe(true);
            expect(
                wrapper.find('input[aria-label="swag-migration.wizard.pages.connectionCreate.gatewayLabel"]').exists(),
            ).toBe(true);
            expect(wrapper.find(primaryButtonSelector).attributes('disabled')).toBeDefined();

            await wrapper.find(connectionNameSelector).setValue(connectionName);
            await flushPromises();
            expect(wrapper.find(primaryButtonSelector).attributes('disabled')).toBeUndefined();

            await wrapper.find(primaryButtonSelector).trigger('click');
            await flushPromises();

            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentials');
            expect(wrapper.find('input[name="sw-field--apiKey"]').element.value).toBe('');
            expect(wrapper.find('input[name="sw-field--apiUser"]').element.value).toBe('');
            expect(wrapper.findComponent(MtUrlField).props('modelValue')).toBe('');
            expect(wrapper.find(primaryButtonSelector).attributes('disabled')).toBeDefined();
            expect(migrationApiServiceMock.createNewConnection).not.toHaveBeenCalled();
            expect(connectionRepositoryMock.save).not.toHaveBeenCalled();
        });

        it('should reject an existing connection name', async () => {
            const { wrapper, router } = await createWrapper();
            await flushPromises();
            connectionRepositoryMock.search.mockResolvedValueOnce([{ ...storedConnection, id: 'existing-connection-id' }]);

            await openCredentialsForm(wrapper);

            expect(wrapper.find('.mt-field__error').exists()).toBe(true);
            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.connectionCreate');
            expect(wrapper.find('.swag-migration-wizard-page-credentials').exists()).toBe(false);
        });

        it('should create the connection with the entered credentials and show success', async () => {
            const { wrapper, router } = await createWrapper();
            await flushPromises();
            await openCredentialsForm(wrapper);
            await fillCredentials(wrapper);

            expect(wrapper.find(primaryButtonSelector).attributes('disabled')).toBeUndefined();
            await wrapper.find(primaryButtonSelector).trigger('click');
            await flushPromises();

            expect(migrationApiServiceMock.createNewConnection).toHaveBeenCalledWith(
                connectionId,
                connectionName,
                profileShopware55,
                gatewayApi,
                { apiKey, apiUser, endpoint },
            );
            expect(migrationApiServiceMock.getConnectionCredentials).toHaveBeenCalledWith(connectionId);
            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentialsSuccess');
            expect(wrapper.find('.swag-migration-wizard-page-credentials-success').isVisible()).toBe(true);
            expect(wrapper.find('button.mt-button--secondary').attributes('style')).toContain('display: none');
            expect(wrapper.find(primaryButtonSelector).attributes('disabled')).toBeUndefined();
        });

        it.each([
            [
                'creating the connection',
                migrationApiServiceMock.createNewConnection,
                apiErrorResponseMock,
            ],
            [
                'reloading credentials with a network error',
                migrationApiServiceMock.getConnectionCredentials,
                new Error('Network Error'),
            ],
            [
                'reloading credentials with a non-API response',
                migrationApiServiceMock.getConnectionCredentials,
                { response: { data: 'Bad Gateway' } },
            ],
        ])('should show an error when %s fails', async (_stage, failingRequest, error) => {
            failingRequest.mockRejectedValueOnce(error);
            const { wrapper, router } = await createWrapper();
            await flushPromises();
            await openCredentialsForm(wrapper);
            await fillCredentials(wrapper);

            await wrapper.find(primaryButtonSelector).trigger('click');
            await flushPromises();

            expect(router.currentRoute.value.name).toBe('swag.migration.wizard.credentialsError');
            expect(wrapper.find('.swag-migration-wizard-page-credentials-error').isVisible()).toBe(true);
            expect(wrapper.find('.swag-migration-wizard__description').text()).toBe(
                'swag-migration.wizard.pages.credentials.error.undefinedErrorMsg',
            );
        });
    });
});
