import { test as base, expect } from '@shopware-ag/acceptance-test-suite';
import type { FixtureTypes } from '@fixtures/AcceptanceTest';

export interface EntityCountExpectation {
    baseline: Map<string, number>;
    expected: Record<string, number>;
}

export interface EntityCounterStruct {
    buildBaseline: (expectedCounts: Record<string, number>) => Promise<EntityCountExpectation>;
    assertBaseline: (expectation: EntityCountExpectation) => Promise<void>;
    assert: (entityName: string, expectedCount: number) => Promise<void>;
}

export const EntityCounter = base.extend<FixtureTypes>({
    EntityCounter: async ({ AdminApiContext }, use) => {
        const getCount = async (entityName: string): Promise<number> => {
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

            if (!response.ok()) {
                return 0;
            }

            const json = await response.json();

            return json.aggregations?.entityCount?.count ?? 0;
        };

        const buildBaseline = async (expectedCounts: Record<string, number>): Promise<EntityCountExpectation> => {
            const baseline = new Map<string, number>();

            for (const entityName of Object.keys(expectedCounts)) {
                baseline.set(entityName, await getCount(entityName));
            }

            return { baseline, expected: expectedCounts };
        };

        const assertBaseline = async ({ baseline, expected }: EntityCountExpectation) => {
            for (const [
                entityName,
                expectedTotal,
            ] of Object.entries(expected)) {
                const currentCount = await getCount(entityName);

                const baselineCount = baseline.get(entityName) ?? 0;

                expect.soft(currentCount - baselineCount, entityName).toBe(expectedTotal);
            }
        };

        const assert = async (entityName: string, expectedCount: number) => {
            const count = await getCount(entityName);

            expect.soft(count, entityName).toBe(expectedCount);
        };

        await use({
            buildBaseline,
            assertBaseline,
            assert,
        });
    },
});
