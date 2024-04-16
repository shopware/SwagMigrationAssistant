import path from 'path';
import dotenv from '@acceptance/node_modules/dotenv';
import { mergeTests } from '@acceptance/fixtures/AcceptanceTest';
import { test as workerFixtures } from '@acceptance/fixtures/WorkerFixtures';
import { test as setupFixtures } from '@acceptance/fixtures/SetupFixtures';
import { test as dataFixtures } from '@acceptance/test-data/DataFixtures';
import { test as storefrontPagesFixtures } from '@acceptance/page-objects/StorefrontPages';
import { test as administrationPagesFixtures } from '@acceptance/page-objects/AdministrationPages';
import { test as shopCustomerTasks } from '@acceptance/tasks/ShopCustomerTasks';
import { test as shopAdminTasks } from '@acceptance/tasks/ShopAdminTasks';

import { MigrationUser } from '@fixtures/MigrationUser';
import { DatabaseCredentials } from '@fixtures/DatabaseCredentials';
import { EntityCounter } from '@fixtures/EntityCounter';

export * from '@acceptance/fixtures/AcceptanceTest';

// Read from "SwagMigrationAssistant/tests/acceptance/.env" file
const pluginAcceptanceDir = path.resolve(__dirname, '../');
dotenv.config({ path: path.resolve(pluginAcceptanceDir, '.env')});

// Read from "platform/.env" file and only set DATABASE_URL if not otherwise set from it
const platformDir = path.resolve(process.cwd(), '../../');
const platformEnv = {};
dotenv.config({ path: path.resolve(platformDir, '.env'), processEnv: platformEnv });
if (!process.env['DATABASE_URL'] && platformEnv['DATABASE_URL']) {
    process.env['DATABASE_URL'] = platformEnv['DATABASE_URL'];
}


export const test = mergeTests(
    workerFixtures,
    setupFixtures,
    dataFixtures,
    storefrontPagesFixtures,
    administrationPagesFixtures,
    shopCustomerTasks,
    shopAdminTasks,
    MigrationUser,
    DatabaseCredentials,
    EntityCounter,
);
