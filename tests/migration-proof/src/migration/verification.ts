import { $ } from 'bun';
import { config } from 'src/lib/config.ts';
import type { EntityCountExpectation } from 'src/lib/types.ts';
import { writeJsonArtifact } from 'src/lib/utils.ts';
import { TargetAdminApiClient } from 'src/api/target.ts';

export type CountBaseline = Record<string, number>;
export type SourceCounts = Record<string, number>;

export async function buildTargetBaseline(
    client: TargetAdminApiClient,
    expectations: EntityCountExpectation[],
): Promise<CountBaseline> {
    const entries = await Promise.all(
        expectations.map(
            async (expectation) =>
                [
                    expectation.targetEntity,
                    await client.searchCount(expectation.targetEntity),
                ] as const,
        ),
    );

    return Object.fromEntries(entries);
}

export async function verifySourceCounts(expectations: EntityCountExpectation[]): Promise<SourceCounts> {
    const entries = await Promise.all(
        expectations.map(async (expectation) => {
            const sourceCount = await querySourceCount(expectation.sourceQuery);

            if (sourceCount <= 0) {
                throw new Error(`Source query for ${expectation.label} returned no rows.`);
            }

            return [
                expectation.label,
                sourceCount,
            ] as const;
        }),
    );

    return Object.fromEntries(entries);
}

export async function verifyEntityCounts(
    client: TargetAdminApiClient,
    expectations: EntityCountExpectation[],
    sourceCounts: SourceCounts,
    baseline: CountBaseline,
): Promise<void> {
    const results = await Promise.all(
        expectations.map(async (expectation) => {
            const targetCurrent = await client.searchCount(expectation.targetEntity);
            const sourceCount = sourceCounts[expectation.label];

            const targetBaseline = baseline[expectation.targetEntity] ?? 0;
            const migratedCount = targetCurrent - targetBaseline;

            return {
                label: expectation.label,
                migratedCount,
                sourceCount,
                expectedTotal: expectation.expectedTotal,
                sourceVersion: config.sourceShopwareVersion,
                targetBaseline,
                targetCurrent,
                targetEntity: expectation.targetEntity,
            };
        }),
    );

    await writeJsonArtifact(config.artifactPath.migratedEntityCounts, results);

    if (config.bootstrap) {
        return;
    }

    const failures: string[] = [];

    for (const result of results) {
        if (result.expectedTotal !== result.migratedCount) {
            failures.push(
                `Entity count mismatch for ${result.label} in source version ${result.sourceVersion}. Expected migrated=${result.expectedTotal}, actual migrated=${result.migratedCount}.`,
            );
        }
    }

    if (failures.length > 0) {
        throw new Error(failures.join('\n'));
    }
}

async function querySourceCount(query: string): Promise<number> {
    const { sourceContainerName: container, sourceDatabaseName: database } = config;

    const statement = `mysql -uroot -proot -N -B -e 'USE ${database}; ${query}'`;
    const result = await $`docker exec ${container} bash -lc ${statement}`.quiet();

    const value = result.stdout.toString().trim();

    if (!/^\d+$/.test(value)) {
        throw new Error(`Unexpected count result for source query "${query}": ${value}`);
    }

    return Number.parseInt(value, 10);
}
