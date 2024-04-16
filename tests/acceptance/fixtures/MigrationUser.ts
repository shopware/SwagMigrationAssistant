import { test as base, expect } from '@acceptance/fixtures/AcceptanceTest';

export const MigrationUser = base.extend({
    migrationUser: async ({ shopAdmin }, use) => {
        // setup
        // ...
        console.log('migrationUser setup');

        await use(shopAdmin);

        // teardown
        // ...
        console.info('SwagMigrationAssistant tests did run, they might leave a whole bunch of data in the shop');
    },
});
