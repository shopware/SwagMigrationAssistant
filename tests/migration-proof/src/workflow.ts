import { resolve } from 'node:path';
import { existsSync } from 'node:fs';
import { $ } from 'bun';
import { config } from 'src/lib/config.ts';
import {
    debugLog,
    fetchWithTimeout,
    formatDuration,
    formatError,
    runDockerCommand,
    sleep,
    writeTextArtifact,
} from 'src/lib/utils.ts';
import { migrationProof } from 'src/migration/proof.ts';

export async function workflow(): Promise<void> {
    const composeFile = resolve(import.meta.dir, '../docker/compose.yaml');
    const envFile = resolveLocalEnvFile();
    const startedAt = Date.now();

    let containersStarted = false;

    try {
        debugLog('Resetting Docker containers');
        await runDockerCommand(
            [
                'down',
                '-v',
                '--remove-orphans',
                '--timeout',
                String(config.docker.stopTimeoutSeconds),
            ],
            composeFile,
            envFile,
            true,
        );

        debugLog('Starting Docker containers');
        await runDockerCommand(
            [
                'up',
                '-d',
                '--force-recreate',
                '--renew-anon-volumes',
                '--build',
            ],
            composeFile,
            envFile,
        );

        containersStarted = true;

        debugLog('Waiting for target API');
        await waitForUrl(config.targetUrl);
        debugLog('Target API is reachable');

        debugLog('Waiting for source bootstrap');
        await waitForSourceReady();
        debugLog('Source bootstrap finished');

        debugLog('Waiting for source shop');
        await waitForUrl(config.sourceUrl);
        debugLog('Source shop is reachable');

        debugLog('Waiting for target console');
        await waitForTargetConsole();
        debugLog('Target console is ready');

        debugLog(`Installing ${config.targetPluginName} in target`);
        await installTargetPlugin();
        debugLog(`${config.targetPluginName} is ready`);

        debugLog('Starting target async worker');
        await startTargetMessengerConsumer();
        debugLog('Target async worker is running');

        await migrationProof();
    } finally {
        if (containersStarted) {
            debugLog('Collecting container logs');

            await Promise.allSettled([
                captureContainerLogs(config.sourceContainerName, config.artifactPath.sourceContainerLog),
                captureContainerLogs(config.targetContainerName, config.artifactPath.targetContainerLog),
            ]);

            debugLog('Stopping Docker containers');
            await runDockerCommand(
                [
                    'down',
                    '-v',
                    '--remove-orphans',
                    '--timeout',
                    String(config.docker.stopTimeoutSeconds),
                ],
                composeFile,
                envFile,
                true,
            );
        }

        const duration = formatDuration(Date.now() - startedAt);
        debugLog(`Duration: ${duration}`);
    }
}

function resolveLocalEnvFile(): string | undefined {
    const envFile = resolve(import.meta.dir, '../.env');

    return existsSync(envFile) ? envFile : undefined;
}

async function waitForUrl(url: string): Promise<void> {
    let lastStatus = 'no response';

    const maxAttempts = config.polling.readinessAttempts;

    for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
        try {
            const response = await fetchWithTimeout(
                url,
                { method: 'GET', redirect: 'manual' },
                config.requestTimeoutMs,
                `Health check for ${url}`,
            );

            if (!response.ok && response.status >= 500) {
                lastStatus = String(response.status);

                throw new Error(lastStatus);
            }

            if (attempt > 1) {
                debugLog(`==> Reachability check succeeded for ${url} on attempt ${attempt}/${maxAttempts}`);
            }

            return;
        } catch (error) {
            lastStatus = formatError(error);

            if (attempt === maxAttempts) {
                throw new Error(`Timed out waiting for ${url}. Last status: ${lastStatus}`);
            }

            debugLog(`--- Still waiting for ${url} (${attempt}/${maxAttempts})`);
            await sleep(config.polling.readinessIntervalMs);
        }
    }
}

async function waitForSourceReady(): Promise<void> {
    const maxAttempts = config.polling.sourceReadyAttempts;

    for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
        const status = await $`docker inspect ${config.sourceContainerName} --format {{.State.Status}}`.quiet().nothrow();

        const containerStatus = status.stdout.toString().trim();

        if (
            [
                'exited',
                'dead',
            ].includes(containerStatus)
        ) {
            const exitCodeResult = await $`docker inspect ${config.sourceContainerName} --format {{.State.ExitCode}}`
                .quiet()
                .nothrow();
            const exitCode = exitCodeResult.stdout.toString().trim();

            throw new Error(
                `Source container stopped before setup finished. Status: ${containerStatus}, exit code: ${exitCode || 'unknown'}.`,
            );
        }

        const result = await $`docker exec ${config.sourceContainerName} test -f ${config.sourceReadyFlagPath}`
            .quiet()
            .nothrow();

        if (result.exitCode === 0) {
            if (attempt > 1) {
                debugLog(`==> Source bootstrap finished on attempt ${attempt}/${maxAttempts}`);
            }

            return;
        }

        if (attempt === maxAttempts) {
            throw new Error('Timed out waiting for source container setup to finish.');
        }

        debugLog(`--- Source bootstrap still running (${attempt}/${maxAttempts})`);
        await sleep(config.polling.sourceReadyIntervalMs);
    }
}

async function captureContainerLogs(containerName: string, relativePath: string): Promise<void> {
    try {
        const result = await $`docker logs ${containerName}`.quiet();

        await writeTextArtifact(relativePath, `${result.stdout.toString()}${result.stderr.toString()}`);
    } catch (error) {
        await writeTextArtifact(relativePath, `${formatError(error)}\n`);
    }
}

async function waitForTargetConsole(): Promise<void> {
    const maxAttempts = config.polling.sourceReadyAttempts;

    for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
        const result = await runTargetConsoleCommand('about');

        if (result.exitCode === 0) {
            if (attempt > 1) {
                debugLog(`==> Target console is ready on attempt ${attempt}/${maxAttempts}`);
            }

            return;
        }

        if (attempt === maxAttempts) {
            throw new Error(`Timed out waiting for target console. Exit code: ${result.exitCode}`);
        }

        debugLog(`--- Target console still warming up (${attempt}/${maxAttempts})`);
        await sleep(config.polling.sourceReadyIntervalMs);
    }
}

async function installTargetPlugin(): Promise<void> {
    const refresh = await runTargetConsoleCommand('plugin:refresh');

    if (refresh.exitCode !== 0) {
        throw new Error(`plugin:refresh failed with exit code ${refresh.exitCode}`);
    }

    const install = await runTargetConsoleCommand(`plugin:install -a ${config.targetPluginName}`);

    const activate = await runTargetConsoleCommand(`plugin:activate ${config.targetPluginName}`);

    if (install.exitCode !== 0 || activate.exitCode !== 0) {
        throw new Error(`Failed to install/activate ${config.targetPluginName}.`);
    }

    const clear = await runTargetConsoleCommand('cache:clear');

    if (clear.exitCode !== 0) {
        throw new Error(`cache:clear failed with exit code ${clear.exitCode}`);
    }

    const verify =
        await $`docker exec ${config.targetContainerName} bash -lc ${`mysql -uroot -proot -h 127.0.0.1 shopware -N -B -e "SELECT active FROM plugin WHERE name='${config.targetPluginName}' LIMIT 1"`}`
            .quiet()
            .nothrow();

    if (verify.exitCode !== 0 || verify.stdout.toString().trim() !== '1') {
        throw new Error(`Plugin ${config.targetPluginName} is not active after installation.`);
    }
}

async function runTargetConsoleCommand(command: string) {
    return $`docker exec ${config.targetContainerName} bash -lc ${`cd /var/www/html && bin/console ${command}`}`
        .quiet()
        .nothrow();
}

async function startTargetMessengerConsumer(): Promise<void> {
    const { logPath, timeLimitSeconds, memoryLimit } = config.messenger;
    const command = `cd /var/www/html && rm -f ${logPath} && bin/console messenger:consume async --time-limit=${timeLimitSeconds} --memory-limit=${memoryLimit} -vv > ${logPath} 2>&1`;

    await $`docker exec -d ${config.targetContainerName} bash -lc ${command}`.quiet();
    await sleep(config.polling.messengerStartupWaitMs);

    const probe = await $`docker exec ${config.targetContainerName} bash -lc ${'pgrep -f "messenger:consume async"'}`
        .quiet()
        .nothrow();

    if (probe.exitCode !== 0) {
        throw new Error('Target messenger consumer did not start successfully.');
    }
}

if (import.meta.main) {
    workflow().catch((error: unknown) => {
        process.stderr.write(`${formatError(error)}\n`);
        process.exitCode = 1;
    });
}
