import 'SwagMigrationAssistant/module/swag-migration/store/migration.store';

const { Store } = Shopware;

describe('core/data/migration.store', () => {
    const store = Store.get('swagMigration');

    beforeEach(() => {
        store.$reset();
    });

    it('Empty premapping should be valid', async () => {
        expect(store.premapping).toStrictEqual([]);
        expect(store.isPremappingValid).toBe(true);
    });

    it('Premapping with missing assignment should be invalid', async () => {
        store.setPremapping([
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

        expect(store.isPremappingValid).toBe(false);
    });

    it('setPremapping should only add mappings and not remove any', async () => {
        // initial set of premapping, e.g. first received by generate-premapping backend call
        store.setPremapping([
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
        store.setPremapping([
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
        expect(store.premapping).toStrictEqual([
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
