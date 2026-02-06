import { defineConfig, devices } from '@playwright/test';
import path from 'path';
import dotenv from 'dotenv';
import { VIEWPORT } from './fixtures/TestHelpers';

const IS_CI = !!process.env.CI;
const UPDATE_SNAPSHOTS = process.env.UPDATE_SNAPSHOTS ===  'true' || process.env.UPDATE_SNAPSHOTS === '1';

const REQUIRED_ENV_VARS = [
    'APP_URL',
    'DATABASE_URL',
];

const PRIORITY_PLATFORM_ENV_VARS = [
    'APP_URL',
    'DATABASE_URL',
    'SHOPWARE_PLAYWRIGHT_IGNORE_HTTPS_ERRORS',
];

loadEnvFiles();
validateRequiredEnvVars();
normalizeUrls();

process.env.SHOPWARE_ADMIN_USERNAME ??= 'admin';
process.env.SHOPWARE_ADMIN_PASSWORD ??= 'shopware';

const ignoreHTTPSErrors = [
    'true',
    '1',
].includes(process.env.SHOPWARE_PLAYWRIGHT_IGNORE_HTTPS_ERRORS ?? '');

export default defineConfig({
    testDir: './tests',
    fullyParallel: true,
    forbidOnly: IS_CI,
    retries: IS_CI ? 2 : 0,
    workers: 1,
    reporter: 'html',
    timeout: 5 * 60_000,
    globalTimeout: 10 * 60_000,
    updateSnapshots: UPDATE_SNAPSHOTS ? 'all' : 'missing',

    use: {
        baseURL: process.env.APP_URL,
        ignoreHTTPSErrors,
        trace: IS_CI ? 'retain-on-failure' : 'on',
        video: IS_CI ? 'retain-on-failure' : 'on',
        screenshot: IS_CI ? 'only-on-failure' : 'on',
        launchOptions: {
            args: IS_CI ? ['--disable-gpu'] : [],
        },
    },

    expect: {
        toHaveScreenshot: {
            maxDiffPixelRatio: 0.01,
            threshold: 0.2,
            animations: 'disabled',
        },
    },

    snapshotDir: './snapshots',
    snapshotPathTemplate: '{snapshotDir}/{testFilePath}/{platform}/{arg}{ext}',

    webServer: {
        command: 'sleep 1d',
        url: process.env.APP_URL,
        reuseExistingServer: true,
        ignoreHTTPSErrors,
    },

    projects: [
        {
            name: 'SwagMigrationAssistant',
            use: {
                ...devices['Desktop Chrome'],
                viewport: VIEWPORT.DEFAULT,
            },
        },
    ],
});

function loadEnvFiles(): void {
    dotenv.config();

    const platformEnvPath = path.resolve(process.cwd(), '../../../../../.env');
    const platformEnv: Record<string, string> = {};

    dotenv.config({ path: platformEnvPath, processEnv: platformEnv });

    for (const key of PRIORITY_PLATFORM_ENV_VARS) {
        process.env[key] ??= platformEnv[key];
    }
}

function validateRequiredEnvVars(): void {
    const missing = REQUIRED_ENV_VARS.filter((key) => !process.env[key]);

    if (missing.length > 0) {
        console.error(`Missing required environment variables:\n- ${missing.join('\n- ')}`);
        console.error(`\nCreate a .env file in: ${path.resolve('.env')}`);
        process.exit(1);
    }
}

function normalizeUrls(): void {
    const normalize = (url: string) => `${url.replace(/\/+$/, '')}/`;

    process.env.APP_URL = normalize(process.env.APP_URL!);
    process.env.ADMIN_URL = process.env.ADMIN_URL ? normalize(process.env.ADMIN_URL) : `${process.env.APP_URL}admin/`;
}
