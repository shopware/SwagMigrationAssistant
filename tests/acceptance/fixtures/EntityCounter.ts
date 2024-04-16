import {test as base, expect, test} from '@acceptance/fixtures/AcceptanceTest';

export const EntityCounter = base.extend({
    entityCounter: async ({ adminApiContext }, use) => {
        const checkEntityCount = async (entityName: string, expectedCount: number) => {
            const stepTitle = `${entityName} is expected to have ${expectedCount} entities`;
            await test.step(stepTitle, async () => {
                const endpointName = entityName.replaceAll('_', '-');
                const response = await adminApiContext.post(`/api/search/${endpointName}`, {
                    data: {
                        limit: 1,
                        includes: {
                            [entityName]: ["id"]
                        },
                        aggregations: [
                            {
                                name: "entityCount",
                                type: "count",
                                field: "id",
                            }
                        ]
                    }
                });
                await expect(response.ok()).toBeTruthy();
                const json = await response.json();

                const count = json.aggregations?.entityCount?.count || 0;

                // soft assertion will not stop the test run, but still mark the test as failed
                await expect.soft(count, stepTitle).toBe(expectedCount);
            });
        };

        await use({
            checkEntityCount
        });
    },
});
