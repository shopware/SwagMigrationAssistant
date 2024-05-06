import { test, expect } from '@fixtures/AcceptanceTest';

const MIGRATION_LOADING_TIMEOUT = 30_000; // 30s (same as request timeout)

test('As a shop owner I want to migrate my data from my old SW5 shop to SW6 via local database connection @SwagMigrationAssistant', async ({
    migrationUser,
    databaseCredentials,
    entityCounter,
 }) => {
    const page = migrationUser.page;
    // allow this complete test to run at max this long
    test.setTimeout(300_000);  // 5 minutes
    await page.goto('/admin#/swag/migration/index/main');
    await expect(page.locator('.sw-loader-element')).toBeHidden({ timeout: MIGRATION_LOADING_TIMEOUT });

    await test.step('Discover that no connection is setup', async () => {
        await expect(page.getByText('No connection')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Start migration' })).toBeDisabled();
        await expect(page.locator('.swag-migration-shop-information__connection-status')).toHaveText('Not connected');

        await expect(page.getByRole('button', { name: 'Create initial connection' })).toBeEnabled();
    });

    await test.step('Setup connection to local SW5 database', async () => {
        await page.getByRole('button', { name: 'Create initial connection' }).click();
        await page.getByRole('button', { name: 'Start', exact: true }).click();
        await page.getByRole('button', { name: 'Continue' }).click();
        await page.getByPlaceholder('Enter name').fill('sw5local');
        await page.locator('div').filter({ hasText: /^Shopware 5\.5 - shopware AG$/ }).click();
        await page.getByText('Shopware 5.7 - shopware AG').click();
        await page.getByText('Select gateway').click();
        await page.getByPlaceholder('Select gateway').fill('Local');
        await page.getByText('Local database').click();
        await page.getByRole('button', { name: 'Establish connection' }).click();
        await page.getByPlaceholder('Enter host').fill(databaseCredentials.host);
        await page.getByLabel('Port').fill(databaseCredentials.port);
        await page.getByPlaceholder('Enter username').fill(databaseCredentials.user);
        await page.getByPlaceholder('Enter password').fill(databaseCredentials.password);
        await page.getByPlaceholder('Enter name').fill(databaseCredentials.database);
        await page.getByPlaceholder('Enter installation root').fill('/tmp');
        await page.getByRole('button', { name: 'Connect' }).click();


        await page.getByRole('button', { name: 'Done' }).click({ timeout: MIGRATION_LOADING_TIMEOUT });
        await expect(page.locator('.sw-loader-element')).toBeHidden({ timeout: MIGRATION_LOADING_TIMEOUT });
        await expect(page.locator('.swag-migration-shop-information__connection-status')).toHaveText('Connected');
    });

    await test.step('Prepare the migration', async () => {
        await page.getByRole('link', { name: 'Data selection' }).click();
        await page.getByLabel('Yes, I would like to continue').check();
        await page.locator('.sw-grid__cell-content').first().click();

        // wait for loading state to finish
        await expect(page.locator('.sw-loader-element')).toBeHidden();
        await expect(page.getByText('Manual assignments')).toBeVisible();
        // select the first available option for all open premappings
        const premappingItems = page.locator('.swag-migration-grid-selection__choice-column select');
        await premappingItems.evaluateAll(async list => {
            for await (const item of list) {
                item.selectedIndex = 1;
                item.dispatchEvent(new Event('change'));
                await new Promise(resolve => setTimeout(resolve, 150));
            }
        });
        await expect(page.getByText('The data check is complete')).toBeVisible();

        // start and inspect the migration process
        await page.getByRole('button', { name: 'Start migration' }).click();
        await expect(page.locator('.sw-loader-element')).toBeHidden({ timeout: MIGRATION_LOADING_TIMEOUT });
    });

    await test.step('Inspect the migration', async () => {
        const steps = await page.locator('.sw-step-display > .sw-step-item').all();
        for await (const step of steps) {
            await expect(step).toHaveClass(/sw-step-item--success/, { timeout: 300_000 }); // 5 min. as really long timeout to wait for each step
        }

        await expect(page.getByText('The Migration Assistant is done')).toBeVisible({ timeout: MIGRATION_LOADING_TIMEOUT });
        await page.getByRole('button', { name: 'Continue' }).click();
    });

    await test.step('Expect entities to be there', async () => {
        await entityCounter.checkEntityCount('swag_migration_logging', 699);

        await entityCounter.checkEntityCount('product', 427);
        await entityCounter.checkEntityCount('product_review', 2);
        await entityCounter.checkEntityCount('category', 63);
        await entityCounter.checkEntityCount('property_group', 14);
        await entityCounter.checkEntityCount('property_group_option', 93);
        await entityCounter.checkEntityCount('product_manufacturer', 14);

        await entityCounter.checkEntityCount('order', 2);
        await entityCounter.checkEntityCount('customer', 3);

        await entityCounter.checkEntityCount('cms_page', 10);
        await entityCounter.checkEntityCount('media', 599);
        await entityCounter.checkEntityCount('media_folder', 26);

        await entityCounter.checkEntityCount('newsletter_recipient', 0);
        await entityCounter.checkEntityCount('promotion', 4);
    });

    await test.step('Inspect the migration history', async () => {
        await page.getByRole('link', { name: 'History' }).click();
        await page.getByRole('row', { name: 'sw5local' }).locator('button').click();
        await page.getByRole('link', { name: 'Show details' }).click();
        await expect(page.getByLabel('Migration details').getByText('sw5local')).toBeVisible();

        await page.getByText('Details', { exact: true }).click();
        const error_row_count = await page.locator('.swag-migration-history-detail-errors tbody > tr').count();
        expect(error_row_count, 'history error detail should have some rows').toBeGreaterThan(0);
    });

    // ToDo MIG-895: finish this download log file implementation when the download works again
    /*
    await test.step('Download log file', async () => {
        // Start waiting for log download before clicking. Note no await.
        const downloadPromise = page.waitForEvent('download');
        await page.getByRole('link', { name: 'download the log file.' }).click();

        // Wait for the download process to complete and save the downloaded file into a buffer
        const download = await downloadPromise;
        const logStream = await download.createReadStream();
        const buffers = [];
        for await (const data of logStream) {
            buffers.push(data);
        }
        const finalBuffer = Buffer.concat(buffers);
        let logString = finalBuffer.toString();

        // cleanup log file uuids
        logString = logString.replaceAll(/"0.+"/g, '"XXX"');

        // snapshot test the migration log file
        await expect.soft(logString).toMatchSnapshot('migration-log-sw5.txt');
    });
    */
});
