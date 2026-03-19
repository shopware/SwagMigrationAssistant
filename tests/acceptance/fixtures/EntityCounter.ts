import { test as base, expect } from '@shopware-ag/acceptance-test-suite';
import type { FixtureTypes } from '@fixtures/AcceptanceTest';

export interface EntityCounterStruct {
    getBaseline: () => Promise<Map<string, number>>;
    assertBaseline: (counts: Map<string, number>) => Promise<void>;
    assert: (entityName: string, expectedCount: number) => Promise<void>;
}

interface DataSelectionResponse {
    entityTotals: Record<string, number>;
}

/**
 * The SKIP_ENTITIES do not have a direct representation in the Shopware 6 database.
 */
const SKIP_ENTITIES = new Set([
    'product_option_relation',
    'product_property_relation',
    'main_variant_relation',
    'translation',
]);

/**
 * The COUNT_CORRECTION object contains the expected difference in entity counts after the
 * migration compared to the baseline. The difference are caused due to duplicates or invalid/incomplete data
 */
const COUNT_CORRECTION = {
    category: 2,
    currency: -2,
    sales_channel: -1,
    number_range: -4,
    product: 26,
    property_group_option: -1,
    product_cross_selling: -150,
    seo_url: -110,
    media: 1,
} as const;

/**
 * Mapping of entity name differences
 */
const ENTITY_NAME_MAP: Record<string, string> = {
    order_document: 'document',
};

export const EntityCounter = base.extend<FixtureTypes>({
    EntityCounter: async ({ AdminApiContext, MigrationConnection }, use) => {
        let baseline = new Map<string, number>();

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

        const getBaseline = async () => {
            const response = await AdminApiContext.get('/api/_action/migration/data-selection', {
                params: { connectionId: MigrationConnection.id },
            });

            expect(response.ok()).toBe(true);

            const dataSelections = (await response.json()) as DataSelectionResponse[];

            const expectedCounts = new Map<string, number>();

            for (const dataSelection of dataSelections) {
                for (const [
                    entityName,
                    total,
                ] of Object.entries(dataSelection.entityTotals)) {
                    if (SKIP_ENTITIES.has(entityName) || total === 0) {
                        // eslint-disable-next-line no-continue
                        continue;
                    }

                    const sw6Name = ENTITY_NAME_MAP[entityName] ?? entityName;

                    if (!expectedCounts.has(sw6Name)) {
                        expectedCounts.set(sw6Name, total);
                    }
                }
            }

            baseline = new Map<string, number>();

            for (const entityName of expectedCounts.keys()) {
                baseline.set(entityName, await getCount(entityName));
            }

            return expectedCounts;
        };

        const assertBaseline = async (expected: Map<string, number>) => {
            for (const [
                entityName,
                expectedTotal,
            ] of expected) {
                const currentCount = await getCount(entityName);

                const baselineCount = baseline.get(entityName) ?? 0;
                const offset = COUNT_CORRECTION[entityName as keyof typeof COUNT_CORRECTION] ?? 0;

                expect.soft(currentCount - baselineCount, entityName).toBe(expectedTotal + offset);
            }
        };

        const assert = async (entityName: string, expectedCount: number) => {
            const count = await getCount(entityName);

            expect.soft(count, entityName).toBe(expectedCount);
        };

        await use({
            getBaseline,
            assertBaseline,
            assert,
        });
    },
});
