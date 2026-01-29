import { test, expect } from '../fixtures/AcceptanceTest';
import { getMask, waitForLoaders } from '../fixtures/TestHelpers';

test.describe('Migration Tests @migration', () => {
    test.describe.configure({
        retries: 0,
        timeout: 300_000,
    });

    test('Perform migration from Shopware 5 to Shopware 6', async ({ ShopAdmin, MigrationConnection: _ }) => {
        const page = ShopAdmin.page;

        await page.goto('/admin');
        await waitForLoaders(page);
    });
});
