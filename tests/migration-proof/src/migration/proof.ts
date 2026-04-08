import { resolve } from 'node:path';
import { config } from 'src/lib/config.ts';
import {
    env,
    debugLog,
    fetchWithTimeout,
    formatError,
    normalizeUrl,
    readJson,
    sleep,
    writeJsonArtifact,
    writeTextArtifact,
} from 'src/lib/utils.ts';
import type { Fixture, GroupedLogResponse, RunEntity } from 'src/lib/types.ts';
import { TargetAdminApiClient } from 'src/api/target.ts';
import { buildTargetBaseline, verifyEntityCounts, verifySourceCounts } from 'src/migration/verification.ts';

const expectedDir = resolve(import.meta.dir, '../../snapshots');

interface MigrationLogArtifacts {
    groupedLogs: GroupedLogResponse;
    logOutput: string;
}

export async function migrationProof(): Promise<void> {
    const fixture = loadFixture();
    const target = new TargetAdminApiClient(config.targetUrl, config.targetAdminUsername, config.targetAdminPassword);

    const sourceCredentials = {
        endpoint: normalizeUrl(config.sourceDockerUrl),
        apiUser: config.sourceApiUser,
        apiKey: config.sourceApiKey,
    };

    await Promise.all([
        assertSourceReachable(),
        target.waitForReady(),
    ]);

    await target.login();

    debugLog('Checking source data coverage');
    const [
        sourceCounts,
        baseline,
    ] = await Promise.all([
        verifySourceCounts(fixture.countExpectations),
        buildTargetBaseline(target, fixture.countExpectations),
    ]);

    const connectionName = `${fixture.connectionName}-${env('GITHUB_RUN_ID', String(Date.now()))}`;
    debugLog(`Creating migration connection "${connectionName}"`);

    const connectionId = await target.createMigrationConnection(
        connectionName,
        fixture.profileName,
        fixture.gatewayName,
        sourceCredentials,
    );

    await target.setSelectedConnection(connectionId);
    await target.checkConnection(connectionId);

    const availableSelections = await target.getDataSelections(connectionId);
    const dataSelectionIds =
        fixture.dataSelectionIds.length > 0
            ? fixture.dataSelectionIds
            : availableSelections.map((selection) => selection.id);

    if (dataSelectionIds.length === 0) {
        throw new Error('No data selections configured for migration proof.');
    }

    debugLog(`Using ${dataSelectionIds.length} data selections: ${dataSelectionIds.join(', ')}`);

    debugLog('Writing premapping');
    await target.generateAndWritePremapping(dataSelectionIds);

    debugLog('Starting migration');
    await target.startMigration(dataSelectionIds);

    const run = await waitForMigrationCompletion(target, connectionId);

    if (!run) {
        throw new Error('No migration run found after starting the migration proof.');
    }

    debugLog('Writing migration logs');
    const { groupedLogs } = await writeMigrationLogs(target, run.id);

    debugLog('Checking grouped migration logs');
    verifyGroupedLogs(groupedLogs.items);

    debugLog('Verifying migrated entity counts');
    await verifyEntityCounts(target, fixture.countExpectations, sourceCounts, baseline);

    debugLog('Migration proof completed successfully');
}

async function waitForMigrationCompletion(target: TargetAdminApiClient, connectionId: string): Promise<RunEntity | null> {
    const states: Record<string, unknown>[] = [];

    const logState = {
        resumedAfterErrorResolution: false,
        lastLoggedStep: null as string | null,
        lastLoggedProgress: -1,
        lastLoggedTotal: -1,
    };

    try {
        for (let attempt = 1; attempt <= config.polling.migrationAttempts; attempt += 1) {
            const [
                state,
                run,
            ] = await Promise.all([
                target.getMigrationState(),
                target.getLatestRun(connectionId),
            ]);

            states.push({
                attempt,
                step: state.step,
                progress: state.progress,
                total: state.total,
                runId: run?.id ?? null,
            });

            if (state.step !== logState.lastLoggedStep) {
                debugLog(`==> ${formatMigrationStep(state.step, state.progress, state.total)}`);

                logState.lastLoggedStep = state.step;
                logState.lastLoggedProgress = state.progress;
                logState.lastLoggedTotal = state.total;
            } else if (
                state.total > 0 &&
                (state.progress !== logState.lastLoggedProgress || state.total !== logState.lastLoggedTotal)
            ) {
                debugLog(`--- Migration progress: ${state.step} (${state.progress}/${state.total})`);

                logState.lastLoggedProgress = state.progress;
                logState.lastLoggedTotal = state.total;
            }

            if (state.step === config.step.errorResolution || state.step === config.step.aborted) {
                if (!run) {
                    throw new Error(`Migration proof entered terminal failure step ${state.step}.`);
                }

                await writeMigrationLogs(target, run.id);
                const unresolvedErrors = await target.getUnresolvedErrorCount(run.id);

                if (unresolvedErrors > 0) {
                    debugLog(`Migration requires manual error resolution: ${unresolvedErrors} unresolved error logs`);

                    throw new Error(`Migration proof entered terminal failure step ${state.step}.`);
                }

                if (state.step !== config.step.errorResolution || logState.resumedAfterErrorResolution) {
                    throw new Error(`Migration proof entered terminal failure step ${state.step}.`);
                }

                debugLog('--- Error resolution reached without blocking errors');

                await target.continueAfterErrorResolution();
                logState.resumedAfterErrorResolution = true;

                await sleep(config.polling.migrationIntervalMs);

                continue;
            }

            logState.resumedAfterErrorResolution = false;

            if (state.step === config.step.waitingForApprove) {
                debugLog('Migration is waiting for approval');

                await target.approveFinishedMigration();
                await sleep(config.polling.migrationIntervalMs);

                continue;
            }

            if (
                run &&
                (
                    [
                        config.step.finished,
                        config.step.aborted,
                        config.step.idle,
                    ] as string[]
                ).includes(run.step)
            ) {
                return run;
            }

            await sleep(config.polling.migrationIntervalMs);
        }

        throw new Error('Migration proof timed out while waiting for completion.');
    } finally {
        await writeJsonArtifact(config.artifactPath.migrationStateHistory, states);
    }
}

async function writeMigrationLogs(target: TargetAdminApiClient, runId: string): Promise<MigrationLogArtifacts> {
    const [
        groupedLogs,
        logOutput,
    ] = await Promise.all([
        target.getGroupedLogsOfRun(runId),
        target.downloadLogsOfRun(runId),
    ]);

    await Promise.all([
        writeJsonArtifact(config.artifactPath.groupedMigrationLogs, groupedLogs),
        writeTextArtifact(config.artifactPath.rawMigrationLogs, logOutput),
    ]);

    return { groupedLogs, logOutput };
}

async function assertSourceReachable(): Promise<void> {
    const response = await fetchWithTimeout(
        config.sourceUrl,
        { method: 'GET', redirect: 'manual' },
        config.requestTimeoutMs,
        `Source reachability check for ${config.sourceUrl}`,
    );

    if (response.status >= 500) {
        throw new Error(`Source system is not reachable at ${config.sourceUrl}. Received status ${response.status}.`);
    }
}

function verifyGroupedLogs(logs: { level: string }[]): void {
    if (config.bootstrap) {
        return;
    }

    const errors = logs.filter((log) => log.level === 'error');

    if (errors.length > 0) {
        throw new Error(`Unexpected migration error logs detected: ${JSON.stringify(errors)}`);
    }
}

function formatMigrationStep(step: string, progress: number, total: number): string {
    if (total <= 0) {
        return `Migration step: ${step}`;
    }

    return `Migration step: ${step} (${progress}/${total})`;
}

function loadFixture(): Fixture {
    const configuredIds = env('MIGRATION_PROOF_DATA_SELECTION_IDS', '').trim();

    return {
        connectionName: config.connection.name,
        profileName: config.connection.profileName,
        gatewayName: config.connection.gatewayName,
        dataSelectionIds: configuredIds
            ? configuredIds
                  .split(',')
                  .map((id) => id.trim())
                  .filter(Boolean)
            : [],
        countExpectations: readJson(expectedDir, 'entity-counts.json'),
    };
}

if (import.meta.main) {
    migrationProof().catch((error: unknown) => {
        process.stderr.write(`${formatError(error)}\n`);
        process.exitCode = 1;
    });
}
