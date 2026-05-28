import { mount } from '@vue/test-utils';
import 'SwagMigrationAssistant/module/swag-migration/store/migration.store';
import swagMigrationPremapping from 'SwagMigrationAssistant/module/swag-migration/component/card/swag-migration-premapping';
import { fixturePreMapping } from '@/fixture';

Shopware.Component.register('swag-migration-premapping', swagMigrationPremapping);

const migrationApiServiceMock = {
    generatePremapping: jest.fn(() => Promise.resolve([])),
    writePremapping: jest.fn(() => Promise.resolve()),
};

const swagMigrationTabCardStub = {
    name: 'swag-migration-tab-card',
    props: ['items'],
    template: `
        <div>
            <slot
                v-for="item in items"
                name="items"
                :item="item"
            />
        </div>
    `,
};

async function createWrapper() {
    return mount(await Shopware.Component.build('swag-migration-premapping'), {
        global: {
            plugins: [Shopware.Store._rootState],
            provide: {
                migrationApiService: migrationApiServiceMock,
            },
            stubs: {
                'swag-migration-tab-card': swagMigrationTabCardStub,
                'swag-migration-grid-selection': true,
            },
        },
    });
}

describe('module/swag-migration/component/card/swag-migration-premapping', () => {
    let store = null;

    beforeEach(() => {
        jest.clearAllMocks();

        store = Shopware.Store.get('swagMigration');
        store.$reset();
    });

    afterEach(() => {
        jest.useRealTimers();
    });

    it('writes partially filled premapping', async () => {
        store.premapping = [
            {
                ...fixturePreMapping[0],
                mapping: [
                    fixturePreMapping[0].mapping[0],
                    {
                        ...fixturePreMapping[0].mapping[1],
                        destinationUuid: null,
                    },
                ],
            },
        ];

        const wrapper = await createWrapper();
        jest.useFakeTimers();
        const gridSelection = wrapper.findComponent({ name: 'swag-migration-grid-selection' });

        gridSelection.vm.$emit('update:value');
        jest.advanceTimersByTime(500);
        await flushPromises();

        expect(migrationApiServiceMock.writePremapping).toHaveBeenCalledTimes(1);
        expect(migrationApiServiceMock.writePremapping).toHaveBeenCalledWith([
            {
                ...store.premapping[0],
                mapping: [
                    store.premapping[0].mapping[0],
                ],
            },
        ]);
    });

    it('persists regenerated partial premapping after changing the data selection', async () => {
        const regeneratedPremapping = [
            {
                ...fixturePreMapping[0],
                mapping: [
                    {
                        ...fixturePreMapping[0].mapping[0],
                        destinationUuid: 'mr',
                    },
                    {
                        ...fixturePreMapping[0].mapping[1],
                        destinationUuid: null,
                    },
                ],
            },
        ];

        migrationApiServiceMock.generatePremapping.mockResolvedValueOnce(regeneratedPremapping);

        await createWrapper();

        store.setDataSelectionIds(['customersOrders']);
        await flushPromises();

        expect(migrationApiServiceMock.generatePremapping).toHaveBeenCalledTimes(1);
        expect(migrationApiServiceMock.generatePremapping).toHaveBeenCalledWith(['customersOrders']);
        expect(migrationApiServiceMock.writePremapping).toHaveBeenCalledTimes(1);
        expect(migrationApiServiceMock.writePremapping).toHaveBeenCalledWith([
            {
                ...store.premapping[0],
                mapping: [
                    regeneratedPremapping[0].mapping[0],
                ],
            },
        ]);
    });

    it('debounces repeated premapping changes into a single save', async () => {
        jest.useFakeTimers();

        store.premapping = fixturePreMapping;

        const wrapper = await createWrapper();
        const gridSelection = wrapper.findComponent({ name: 'swag-migration-grid-selection' });

        gridSelection.vm.$emit('update:value');
        gridSelection.vm.$emit('update:value');

        expect(store.isLoading).toBe(true);
        expect(migrationApiServiceMock.writePremapping).not.toHaveBeenCalled();

        jest.advanceTimersByTime(500);
        await flushPromises();

        expect(migrationApiServiceMock.writePremapping).toHaveBeenCalledTimes(1);
        expect(store.isLoading).toBe(false);
    });
});
