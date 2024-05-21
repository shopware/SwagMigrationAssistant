import { test, expect } from '../fixtures/AcceptanceTest';

const MIGRATION_LOADING_TIMEOUT = 30_000; // 30s (same as request timeout)

// configuration for all the tests in this file
test.describe.configure({
    retries: 0, // this test isn't retryable because it doesn't reset state
    timeout: 300_000, // 5 minutes
});

test('As a shop owner I want to migrate my data from my old SW5 shop to SW6 via local database connection @SwagMigrationAssistant', async ({
    MigrationUser,
    DatabaseCredentials,
    EntityCounter,
    MediaProcessObserver,
 }) => {
    const page = MigrationUser.page;
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
        await page.getByPlaceholder('Enter host').fill(DatabaseCredentials.host);
        await page.getByLabel('Port').fill(DatabaseCredentials.port);
        await page.getByPlaceholder('Enter username').fill(DatabaseCredentials.user);
        await page.getByPlaceholder('Enter password').fill(DatabaseCredentials.password);
        await page.getByPlaceholder('Enter name').fill(DatabaseCredentials.database);
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

    // ToDo MIG-985: Remove this if the underlying issue is fixed
    await test.step('Wait for media download to finish', async () => {
        await expect.poll(async () => {
            return await MediaProcessObserver.isMediaProcessing();
        }, {
            // Probe after 100ms and then every second
            intervals: [100, 1_000],
            timeout: 300_000,
        }).toBe(false);
    });

    await test.step('Expect entities to be there', async () => {
        await EntityCounter.checkEntityCount('swag_migration_logging', 699);

        await EntityCounter.checkEntityCount('product', 427);
        await EntityCounter.checkEntityCount('product_review', 2);
        await EntityCounter.checkEntityCount('category', 63);
        await EntityCounter.checkEntityCount('property_group', 14);
        await EntityCounter.checkEntityCount('property_group_option', 93);
        await EntityCounter.checkEntityCount('product_manufacturer', 14);

        await EntityCounter.checkEntityCount('order', 2);
        await EntityCounter.checkEntityCount('customer', 3);

        await EntityCounter.checkEntityCount('cms_page', 10);
        await EntityCounter.checkEntityCount('media', 603);
        await EntityCounter.checkEntityCount('media_folder', 26);

        await EntityCounter.checkEntityCount('newsletter_recipient', 0);
        await EntityCounter.checkEntityCount('promotion', 4);
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

    await test.step('Download log file', async () => {
        // Start waiting for log download before clicking. Note no await.
        const downloadPromise = page.waitForEvent('download', { timeout: 300_000 });
        await page.getByRole('link', { name: 'download the log file.' }).click();

        // Wait for the download process to complete and save the downloaded file as an artifact and into a buffer
        const download = await downloadPromise;
        await download.saveAs('test-results/migration-log-sw5_unmodified.txt');
        const logStream = await download.createReadStream();
        const buffers = [];
        for await (const data of logStream) {
            buffers.push(data);
        }
        const finalBuffer = Buffer.concat(buffers);
        let logString = finalBuffer.toString();

        // cleanup file timestamps
        logString = logString.replaceAll(/[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{2,4}\s[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}\sUTC/g, 'TimestampXXX');

        // cleanup log file uuids
        logString = logString.replaceAll(/(\s|")(0[0-9a-fA-F]+)/g, '$1XXX');

        // cleanup any media / document file logs, they are inherently non-deterministic,
        // because media download messages are all put into the message queue and the order they are processed isn't deterministic
        logString = logString.replaceAll(/(\[warning] SWAG_MIGRATION_CANNOT_GET_).+\n.+\n.+/g, '$1XXX\nXXX\nXXX');

        // Add more replace logic here, if anything new non-deterministic shows up...

        // snapshot test the migration log file
        expect.soft(logString).toMatchSnapshot('migration-log-sw5.txt');
    });
});
