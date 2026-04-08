import { config } from 'src/lib/config.ts';
import { readFileSync } from 'node:fs';
import { mkdir } from 'node:fs/promises';
import { dirname, resolve as nodeResolve } from 'node:path';

export async function runDockerCommand(
    args: string[],
    composeFile: string,
    envFile?: string,
    ignoreFailure = false,
): Promise<void> {
    const command = [
        'docker',
        'compose',
        '--project-name',
        'migration-proof',
        '-f',
        composeFile,
        ...args,
    ];

    if (envFile) {
        command.splice(4, 0, '--env-file', envFile);
    }

    const proc = Bun.spawn(command, {
        stdout: ignoreFailure ? 'ignore' : 'inherit',
        stderr: ignoreFailure ? 'ignore' : 'inherit',
    });

    const exitCode = await proc.exited;

    if (exitCode !== 0 && !ignoreFailure) {
        throw new Error(`Command failed with exit code ${exitCode}: ${command.join(' ')}`);
    }
}

export async function fetchWithTimeout(
    input: string | URL | Request,
    init: RequestInit | undefined,
    timeoutMs: number,
    label: string,
): Promise<Response> {
    try {
        return await fetch(input, {
            ...init,
            signal: AbortSignal.timeout(timeoutMs),
        });
    } catch (error) {
        if (error instanceof Error && (error.name === 'TimeoutError' || error.name === 'AbortError')) {
            throw new Error(`${label} timed out after ${timeoutMs}ms.`);
        }

        throw error;
    }
}

export async function retry(task: () => Promise<void>, attempts: number, delayMs: number): Promise<void> {
    let lastError: unknown;

    for (let attempt = 1; attempt <= attempts; attempt += 1) {
        try {
            await task();

            return;
        } catch (error) {
            lastError = error;

            if (attempt < attempts) {
                await sleep(delayMs);
            }
        }
    }

    throw lastError;
}

export function env(name: string, defaultValue: string | null = null): string {
    const value = process.env[name];

    if (value !== undefined && value !== '') {
        return value;
    }

    if (defaultValue === null) {
        throw new Error(`Environment variable ${name} is not set or empty`);
    }

    return defaultValue;
}

export function debugLog(message: string): void {
    if (!config.debug) {
        return;
    }

    process.stdout.write(`\x1b[95m[migration-proof] ${message}\x1b[0m\n`);
}

export function isEnabled(value: string | undefined): boolean {
    return [
        '1',
        'true',
        'yes',
    ].includes((value ?? '').toLowerCase());
}

export function readJson<T>(dir: string, fileName: string): T {
    return JSON.parse(readFileSync(nodeResolve(dir, fileName), 'utf-8')) as T;
}

async function writeArtifact(relativePath: string, content: string): Promise<void> {
    const fullPath = nodeResolve(config.outputDir, relativePath);

    await mkdir(dirname(fullPath), { recursive: true });
    await Bun.write(fullPath, content);
}

export async function writeJsonArtifact(relativePath: string, data: unknown): Promise<void> {
    await writeArtifact(relativePath, `${JSON.stringify(data, null, 4)}\n`);
}

export async function writeTextArtifact(relativePath: string, content: string): Promise<void> {
    await writeArtifact(relativePath, content);
}

export function formatError(error: unknown): string {
    return error instanceof Error ? (error.stack ?? error.message) : String(error);
}

export function formatDuration(durationMs: number): string {
    const totalSeconds = Math.max(0, Math.round(durationMs / 1000));
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${minutes}m ${seconds}s`;
}

export function sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

export function normalizeUrl(value: string): string {
    return value.replace(/\/+$/, '');
}
