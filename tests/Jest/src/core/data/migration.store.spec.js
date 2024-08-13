import originalStore from 'SwagMigrationAssistant/core/data/migration.store';

const { cloneDeep } = Shopware.Utils.object;

describe('core/data/migration.store', () => {
    it('Empty premapping should be valid', async () => {
        const store = cloneDeep(originalStore);

        expect(store.state.premapping).toStrictEqual([]);
        expect(store.getters.isPremappingValid(store.state)).toBe(true);
    });

    it('Premapping with missing assignment should be invalid', async () => {
        const store = cloneDeep(originalStore);

        store.mutations.setPremapping(store.state, [
            {
                entity: 'payment_method',
                choices: [
                    {
                        description: 'Cash on delivery',
                        uuid: 'uuid-cash-on-delivery',
                    },
                    {
                        description: 'Invoice',
                        uuid: 'uuid-invoice',
                    },
                ],
                mapping: [
                    {
                        description: 'Lastschrift',
                        sourceId: '2',
                        destinationUuid: null,
                    },
                    {
                        description: 'Rechnung',
                        sourceId: '4',
                        destinationUuid: 'uuid-invoice',
                    },
                ],
            },
        ]);

        expect(store.getters.isPremappingValid(store.state)).toBe(false);
    });

    it('setPremapping should only add mappings and not remove any', async () => {
        const store = cloneDeep(originalStore);

        // initial set of premapping, e.g. first received by generate-premapping backend call
        store.mutations.setPremapping(store.state, [
            {
                entity: 'payment_method',
                choices: [
                    {
                        description: 'Cash on delivery',
                        uuid: 'uuid-cash-on-delivery',
                    },
                ],
                mapping: [
                    {
                        description: 'Lastschrift',
                        sourceId: '2',
                        destinationUuid: '',
                    },
                ],
            },
        ]);

        // second set of premapping, e.g. by second generate-premapping backend call after the data selection changed
        store.mutations.setPremapping(store.state, [
            {
                entity: 'payment_method',
                choices: [
                    {
                        description: 'Cash on delivery',
                        uuid: 'uuid-cash-on-delivery',
                    },
                    {
                        description: 'Invoice',
                        uuid: 'uuid-invoice',
                    },
                ],
                mapping: [
                    {
                        description: 'Lastschrift',
                        sourceId: '2',
                        destinationUuid: 'uuid-lastschrift',
                    },
                    {
                        description: 'Rechnung',
                        sourceId: '4',
                        destinationUuid: null,
                    },
                ],
            },
        ]);

        // compare final state
        expect(store.state.premapping).toStrictEqual([
            {
                entity: 'payment_method',
                choices: [
                    {
                        description: 'Cash on delivery',
                        uuid: 'uuid-cash-on-delivery',
                    },
                    {
                        description: 'Invoice',
                        uuid: 'uuid-invoice',
                    },
                ],
                mapping: [
                    // empty mappings will be pushed to the front
                    {
                        id: 'payment_method-4',
                        description: 'Rechnung',
                        sourceId: '4',
                        destinationUuid: null,
                    },
                    {
                        id: 'payment_method-2',
                        description: 'Lastschrift',
                        sourceId: '2',
                        destinationUuid: 'uuid-lastschrift',
                    },
                ],
            },
        ]);
    });
});
