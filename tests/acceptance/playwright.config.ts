import { defineConfig, devices } from '@playwright/test';
import path from 'path';
import dotenv from 'dotenv';

// Read from "SwagMigrationAssistant/tests/acceptance/.env" file
dotenv.config();

// Read from "platform/.env" file and only set DATABASE_URL + APP_URL if not otherwise set from it
const platformDir = path.resolve(process.cwd(), '../../../../..');
const platformEnv = {};
dotenv.config({ path: path.resolve(platformDir, '.env'), processEnv: platformEnv });
if (!process.env['DATABASE_URL'] && platformEnv['DATABASE_URL']) {
    process.env['DATABASE_URL'] = platformEnv['DATABASE_URL'];
}
if (!process.env['APP_URL'] && platformEnv['APP_URL']) {
    process.env['APP_URL'] = platformEnv['APP_URL'];
}

const missingEnvVars = ['APP_URL', 'DATABASE_URL'].filter((envVar) => {
    return process.env[envVar] === undefined;
});

if (missingEnvVars.length > 0) {
    const envPath = path.resolve('.env');

    process.stdout.write(`Please provide the following env vars (loaded env: ${envPath}):\n`);
    process.stdout.write('- ' + missingEnvVars.join('\n- ') + '\n');

    process.exit(1);
}

process.env['SHOPWARE_ADMIN_USERNAME'] = process.env['SHOPWARE_ADMIN_USERNAME'] || 'admin';
process.env['SHOPWARE_ADMIN_PASSWORD'] = process.env['SHOPWARE_ADMIN_PASSWORD'] || 'shopware';

// make sure APP_URL ends with a slash
process.env['APP_URL'] = process.env['APP_URL'].replace(/\/+$/, '') + '/';
if (process.env['ADMIN_URL']) {
    process.env['ADMIN_URL'] = process.env['ADMIN_URL'].replace(/\/+$/, '') + '/';
} else {
    process.env['ADMIN_URL'] = process.env['APP_URL'] + 'admin/';
}

export default defineConfig({
    testDir: './tests',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : 1,
    reporter: 'html',

    use: {
        /* Base URL to use in actions like `await page.goto('/')`. */
        baseURL: process.env['APP_URL'],
        trace: 'on',
        video: 'off',
    },

    // We abuse this to wait for the external webserver
    webServer: {
        command: 'sleep 1d',
        url: process.env['APP_URL'],
        reuseExistingServer: true,
    },

    projects: [
        {
            name: 'SwagMigrationAssistant',
            use: {
                ...devices['Desktop Chrome'],
            },
        },
    ],
});
