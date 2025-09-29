import { mount } from '@vue/test-utils';
import swagMigrationTabCard from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-tab-card';
import swagMigrationPremapping from 'SwagMigrationAssistant/module/swag-migration/component/card/swag-migration-premapping';
import { MIGRATION_STORE_ID } from 'SwagMigrationAssistant/module/swag-migration/store/migration.store';
import { premappingFixture } from '@/fixtures';

Shopware.Component.register('swag-migration-tab-card', swagMigrationTabCard);
Shopware.Component.register('swag-migration-premapping', swagMigrationPremapping);

const migrationApiServiceMock = {
    generatePremapping: jest.fn(() => Promise.resolve([premappingFixture])),
    writePremapping: jest.fn(() => Promise.resolve()),
};

const createWrapper = async () => {
    return mount(await Shopware.Component.build('swag-migration-premapping'), {
        global: {
            stubs: {
                'swag-migration-tab-card': await Shopware.Component.build('swag-migration-tab-card'),
                'swag-migration-tab-card-item': true,
                'swag-migration-grid-selection': true,
                'sw-tabs-item': true,
                'sw-tabs': true,
            },
            provide: {
                migrationApiService: migrationApiServiceMock,
            },
        },
    });
};

describe('src/module/swag-migration/component/card/swag-migration-premapping', () => {
    beforeEach(() => {
        const store = Shopware.Store.get(MIGRATION_STORE_ID);

        store.premapping = [premappingFixture];
        store.dataSelectionIds = ['productReviews'];
    });

    it('should render loading card & hide tab card if premapping is being fetched', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.swag-migration-premapping__loading-card').exists()).toBe(false);
        expect(wrapper.find('.swag-migration-premapping__tab-card').exists()).toBe(true);
        expect(wrapper.find('.swag-migration-premapping__success-card').exists()).toBe(true);
        Shopware.Store.get(MIGRATION_STORE_ID).dataSelectionIds = ['productReviews'];
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.swag-migration-premapping__loading-card').exists()).toBe(true);
        expect(wrapper.find('.swag-migration-premapping__tab-card').exists()).toBe(false);
        expect(wrapper.find('.swag-migration-premapping__success-card').exists()).toBe(false);
        expect(Shopware.Store.get(MIGRATION_STORE_ID).isLoading).toBe(true);
        await flushPromises();

        expect(wrapper.find('.swag-migration-premapping__loading-card').exists()).toBe(false);
        expect(wrapper.find('.swag-migration-premapping__tab-card').exists()).toBe(true);
        expect(wrapper.find('.swag-migration-premapping__success-card').exists()).toBe(true);
        expect(Shopware.Store.get(MIGRATION_STORE_ID).isLoading).toBe(false);

        expect(migrationApiServiceMock.generatePremapping).toHaveBeenCalledTimes(1);
        expect(migrationApiServiceMock.writePremapping).toHaveBeenCalledTimes(1);
    });

    it('should render unfilled card if premapping is invalid', async () => {
        Shopware.Store.get(MIGRATION_STORE_ID).premapping = [
            {
                ...premappingFixture,
                mapping: [
                    premappingFixture.mapping[0],
                    {
                        ...premappingFixture.mapping[1],
                        destinationUuid: null,
                    },
                ],
            },
        ];

        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.swag-migration-premapping__unfilled-title').exists()).toBe(true);
        expect(wrapper.find('.swag-migration-premapping__unfilled-caption').exists()).toBe(true);
        expect(wrapper.find('.swag-migration-premapping__alert').exists()).toBe(true);
    });

    it('should render success card if premapping is valid', async () => {
        Shopware.Store.get(MIGRATION_STORE_ID).premapping = [];

        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.find('.swag-migration-premapping__success-bubble').exists()).toBe(false);
        expect(wrapper.find('.swag-migration-premapping__success-title').exists()).toBe(false);
        expect(wrapper.find('.swag-migration-premapping__success-caption').exists()).toBe(false);

        Shopware.Store.get(MIGRATION_STORE_ID).premapping = [premappingFixture];
        await flushPromises();

        expect(wrapper.find('.swag-migration-premapping__success-bubble').exists()).toBe(true);
        expect(wrapper.find('.swag-migration-premapping__success-title').exists()).toBe(true);
        expect(wrapper.find('.swag-migration-premapping__success-caption').exists()).toBe(true);
    });
});
