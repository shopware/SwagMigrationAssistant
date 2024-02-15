import { test as base } from '@shopware-ag/acceptance-test-suite';
import {FixtureTypes} from './AcceptanceTest';

export const MigrationUser = base.extend<FixtureTypes>({
    MigrationUser: async ({ ShopAdmin }, use) => {
        // setup
        // ...

        await use(ShopAdmin);

        // teardown
        // ...
        console.warn('SwagMigrationAssistant tests did run, they might leave a whole bunch of data in the shop');
    },
});
