import {test as base, expect, test} from '@shopware-ag/acceptance-test-suite';
import {FixtureTypes} from './AcceptanceTest';

export interface EntityCounterStruct {
    checkEntityCount: (entityName: string, expectedCount: number) => Promise<void>;
}

export const EntityCounter = base.extend<FixtureTypes>({
    EntityCounter: async ({ AdminApiContext }, use) => {
        const checkEntityCount = async (entityName: string, expectedCount: number) => {
            const stepTitle = `${entityName} is expected to have ${expectedCount} entities`;
            await test.step(stepTitle, async () => {
                const endpointName = entityName.replaceAll('_', '-');
                const response = await AdminApiContext.post(`/api/search/${endpointName}`, {
                    data: {
                        limit: 1,
                        includes: {
                            [entityName]: ['id'],
                        },
                        aggregations: [
                            {
                                name: 'entityCount',
                                type: 'count',
                                field: 'id',
                            },
                        ],
                    },
                });
                expect(response.ok()).toBeTruthy();
                const json = await response.json();

                // eslint-disable-next-line playwright/no-conditional-in-test
                const count = json.aggregations?.entityCount?.count || 0;

                // soft assertion will not stop the test run, but still mark the test as failed
                expect.soft(count, stepTitle).toBe(expectedCount);
            });
        };

        await use({
            checkEntityCount,
        });
    },
});
