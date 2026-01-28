import { defineConfig, devices } from '@playwright/test';
import path from 'path';
import dotenv from 'dotenv';

// read 'SwagMigrationAssistant/tests/acceptance/.env'
dotenv.config();

// read 'platform/.env'
const platformDir = path.resolve(process.cwd(), '../../../../..');

const platformEnv = {} as {
    DATABASE_URL?: string;
    APP_URL?: string;
};

dotenv.config({
    path: path.resolve(platformDir, '.env'),
    processEnv: platformEnv,
});

if (!process.env.DATABASE_URL && platformEnv?.DATABASE_URL) {
    process.env.DATABASE_URL = platformEnv?.DATABASE_URL;
}

if (!process.env.APP_URL && platformEnv?.APP_URL) {
    process.env.APP_URL = platformEnv?.APP_URL;
}

const missingEnvVars = [
    'APP_URL',
    'DATABASE_URL',
].filter((envVar) => {
    return process.env[envVar] === undefined;
});

if (missingEnvVars.length > 0) {
    const envPath = path.resolve('.env');

    process.stdout.write(`Please provide the following env vars (loaded env: ${envPath}):\n`);
    process.stdout.write(`- ${missingEnvVars.join('\n- ')}\n`);

    process.exit(1);
}

process.env.SHOPWARE_ADMIN_USERNAME = process.env.SHOPWARE_ADMIN_USERNAME ?? 'admin';
process.env.SHOPWARE_ADMIN_PASSWORD = process.env.SHOPWARE_ADMIN_PASSWORD ?? 'shopware';

// make sure APP_URL ends with a slash
process.env.APP_URL = `${process.env.APP_URL?.replace(/\/+$/, '')}/`;

if (process.env.ADMIN_URL) {
    process.env.ADMIN_URL = `${process.env.ADMIN_URL.replace(/\/+$/, '')}/`;
} else {
    process.env.ADMIN_URL = `${process.env.APP_URL}admin/`;
}

export default defineConfig({
    testDir: './tests',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : 1,
    reporter: 'html',
    timeout: 300_000, // 5 min
    globalTimeout: 600_000, // 10 min

    use: {
        baseURL: process.env.APP_URL,
        trace: 'retain-on-failure',
        video: 'retain-on-failure',
        screenshot: 'only-on-failure',
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
    },

    projects: [
        {
            name: 'SwagMigrationAssistant',
            use: {
                ...devices['Desktop Chrome'],
                viewport: { width: 1440, height: 1080 },
            },
        },
    ],
});
