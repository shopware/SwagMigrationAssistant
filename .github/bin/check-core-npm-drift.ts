import { appendFile, readFile } from 'node:fs/promises';
import { join } from 'node:path';

const CORE_PACKAGE_JSON_URL =
    'https://raw.githubusercontent.com/shopware/shopware/trunk/src/Administration/Resources/app/administration/package.json';

const PLUGIN_PACKAGE_DIRS = [
    'src/Resources/app/administration',
    'tests/Jest',
    'tests/acceptance',
];

interface PackageJson {
    dependencies?: Record<string, string>;
    devDependencies?: Record<string, string>;
}

interface PluginPackageJson {
    devDependencies: Record<string, string>;
}

interface Drift {
    dir: string;
    name: string;
    plugin: string;
    core: string;
}

async function loadCorePackageJson(): Promise<PackageJson> {
    const localPath = process.env.CORE_PACKAGE_JSON;

    if (localPath) {
        return JSON.parse(await readFile(localPath, 'utf8')) as PackageJson;
    }

    const response = await fetch(CORE_PACKAGE_JSON_URL);

    if (!response.ok) {
        throw new Error(`Fetching ${CORE_PACKAGE_JSON_URL} failed with HTTP ${response.status}`);
    }

    return (await response.json()) as PackageJson;
}

function versionsOf(packageJson: PackageJson): Record<string, string> {
    return { ...packageJson.dependencies, ...packageJson.devDependencies };
}

function formatSummary(drifts: Drift[], compared: number): string {
    if (drifts.length === 0) {
        return `## npm drift against core trunk\n\nNo drift, ${compared} shared packages compared.\n`;
    }

    const rows = drifts.map((drift) => `| \`${drift.dir}\` | \`${drift.name}\` | ${drift.plugin} | ${drift.core} |`);

    return [
        '## npm drift against core trunk',
        '',
        `${drifts.length} of ${compared} shared packages differ from core.`,
        '',
        '| Folder | Package | Plugin | Core |',
        '| --- | --- | --- | --- |',
        ...rows,
        '',
        'Run from the plugin root:',
        '',
        '```sh',
        installCommands(drifts),
        '```',
        '',
    ].join('\n');
}

function installCommands(drifts: Drift[]): string {
    return PLUGIN_PACKAGE_DIRS.filter((dir) => drifts.some((drift) => drift.dir === dir))
        .map((dir) => {
            const packages = drifts.filter((drift) => drift.dir === dir).map((drift) => `${drift.name}@${drift.core}`);

            return `npm install --save-exact --prefix ${dir} ${packages.join(' ')}`;
        })
        .join('\n');
}

async function main(): Promise<void> {
    const root = join(import.meta.dirname, '../..');

    const coreVersions = versionsOf(await loadCorePackageJson());

    const drifts: Drift[] = [];
    let compared = 0;

    for (const dir of PLUGIN_PACKAGE_DIRS) {
        const pluginPackageJson = JSON.parse(await readFile(join(root, dir, 'package.json'), 'utf8')) as PluginPackageJson;
        const pluginVersions = pluginPackageJson.devDependencies;

        for (const name of Object.keys(pluginVersions)) {
            const plugin = pluginVersions[name];
            const core = coreVersions[name];

            if (core === undefined) {
                continue;
            }

            compared++;

            if (plugin !== core) {
                drifts.push({
                    dir,
                    name,
                    plugin,
                    core,
                });
            }
        }
    }

    if (compared === 0) {
        throw new Error('No shared packages with core found, the comparison did not run');
    }

    const summary = formatSummary(drifts, compared);
    process.stdout.write(summary);

    if (process.env.GITHUB_STEP_SUMMARY) {
        await appendFile(process.env.GITHUB_STEP_SUMMARY, summary);
    }

    if (drifts.length > 0) {
        process.exitCode = 1;
    }
}

main().catch(async (error: unknown) => {
    console.error(error);
    process.exitCode = 1;

    if (process.env.GITHUB_STEP_SUMMARY) {
        await appendFile(
            process.env.GITHUB_STEP_SUMMARY,
            `## npm drift against core trunk\n\nThe check could not run: ${String(error)}\n`,
        );
    }
});
