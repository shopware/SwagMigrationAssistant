import { test, expect } from '../fixtures/AcceptanceTest';
import { getMask, waitForLoaders, withLargerViewport } from '../fixtures/TestHelpers';

test.describe('Migration Tests @migration @visual', () => {
    test.describe.configure({
        retries: 0,
        timeout: 300_000,
    });

    test('Perform migration from Shopware 5 to Shopware 6', async ({ ShopAdmin, MigrationConnection: _ }) => {
        const page = ShopAdmin.page;
        const mask = getMask(page);

        await page.goto('/admin');
        await waitForLoaders(page);

        await test.step('Prepare migration', async () => {
            await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
            await waitForLoaders(page);

            await page.getByTitle('Data selection').click();
            await waitForLoaders(page);

            await page.locator('.swag-migration-data-selector input[type="checkbox"]').first().check();
            await waitForLoaders(page);

            const checkboxes = page.locator('.swag-migration-data-selector input[type="checkbox"]');

            await expect(checkboxes).toHaveCount(await checkboxes.count());

            for (const checkbox of await checkboxes.all()) {
                await expect(checkbox).toBeChecked();
            }

            await page.locator('.swag-migration-tab-card').scrollIntoViewIfNeeded();
            await waitForLoaders(page);

            const restoreViewport = await withLargerViewport(page);
            await expect(page).toHaveScreenshot('data-selection-assigment-with-errors.png', { mask });
            await restoreViewport();

            const tabs = page.locator('.swag-migration-tab-card__title');

            for (const tab of await tabs.all()) {
                await tab.click();

                const errorInputSelector =
                    '.swag-migration-grid-selection__choice-column .has--error .mt-select-selection-list__input';
                let errorInput = page.locator(errorInputSelector).first();

                while (await errorInput.isVisible()) {
                    await errorInput.click();

                    await page.locator('.mt-select-result').first().click();
                    await waitForLoaders(page);

                    errorInput = page.locator(errorInputSelector).first();
                }
            }

            await expect(page).toHaveScreenshot('data-selection-assigment-without-errors.png', { mask });
        });

        await test.step('Start migration', async () => {
            await page.getByRole('button', { name: 'Start migration' }).click();
            await waitForLoaders(page);

            await page.getByRole('button', { name: 'Continue anyway' }).click();
            await waitForLoaders(page);

            await expect(page).toHaveScreenshot('migration-process-started.png', { mask });

            const step = page.locator('.sw-step-display > .sw-step-item').first();
            await expect(step).toHaveClass(/sw-step-item--success/, { timeout: 300_000 });

            await waitForLoaders(page);
            await expect(page.getByText('Review and resolve migration errors')).toBeVisible({ timeout: 300_000 });
        });

        await test.step('Error resolution', async () => {
            let restoreViewport = await withLargerViewport(page);
            await expect(page).toHaveScreenshot('error-resolution-log-groups-unfixed.png', { mask });
            await restoreViewport();

            await waitForLoaders(page);
            const logs = page.locator('.sw-data-grid__body .sw-data-grid__cell--actions');

            const processLogEntry = async (index: number) => {
                await logs.nth(index).getByRole('button', { name: 'Open actions menu' }).click();
                await page.getByRole('button', { name: 'Edit' }).click();
                await waitForLoaders(page);

                await page.locator('.mt-field--checkbox').first().click();
                await page.getByRole('button', { name: /Select all \(\d+\)/ }).click();
                await waitForLoaders(page);

                await page.locator('.swag-migration-error-resolution-field-relation .sw-select__selection').click();
                await page.locator('.sw-select-result').first().click();
                await page.locator('.sw-modal__title').first().click();
            };

            const logCount = await logs.count();

            for (let i = 0; i < logCount; i++) {
                await processLogEntry(i);

                // eslint-disable-next-line playwright/no-conditional-in-test
                if (i === 0) {
                    // eslint-disable-next-line playwright/no-conditional-expect
                    await expect(page).toHaveScreenshot('error-resolution-log-detail-unfixed.png', { mask });
                }

                await page.getByRole('button', { name: 'Apply changes' }).click();
                await waitForLoaders(page);

                // eslint-disable-next-line playwright/no-conditional-in-test
                if (i === 0) {
                    // eslint-disable-next-line playwright/no-conditional-expect
                    await expect(page).toHaveScreenshot('error-resolution-log-detail-fixed.png', { mask });
                }

                await page.locator('.sw-modal__close').click();
                await waitForLoaders(page);
            }

            await waitForLoaders(page);

            restoreViewport = await withLargerViewport(page);
            await expect(page).toHaveScreenshot('error-resolution-log-groups-fixed.png', { mask });
            await restoreViewport();

            await expect(page.locator('.swag-migration-error-resolution-step__card-table-count-icon')).toHaveCount(logCount);

            await page.getByRole('button', { name: 'Continue' }).click();
        });

        await test.step('Finish migration', async () => {
            const steps = await page.locator('.sw-step-display > .sw-step-item').all();

            for (const step of steps) {
                await expect(step).toHaveClass(/sw-step-item--success/, { timeout: 300_000 });
            }

            await waitForLoaders(page);
            await expect(page.getByText('The Migration Assistant is done')).toBeVisible({ timeout: 300_000 });

            await expect(page).toHaveScreenshot('migration-process-summary.png', { mask });

            await page.getByRole('button', { name: 'Back to overview' }).click();
            await waitForLoaders(page);
        });

        await test.step('Inspect migration history', async () => {
            await page.getByTitle('History').click();
            await waitForLoaders(page);

            await expect(page).toHaveScreenshot('migration-history-list.png', { mask });

            await page.locator('.sw-data-grid__body .sw-data-grid__actions-menu').getByRole('button').click();
            await page.getByRole('button', { name: 'Show details' }).click();
            await waitForLoaders(page);

            await expect(page).toHaveScreenshot('migration-history-details-modal.png', { mask });
        });

        await test.step('Verify migration logs', async () => {
            await page.locator('.sw-modal__close').click();
            await waitForLoaders(page);

            await page.locator('.sw-data-grid__body .sw-data-grid__actions-menu').getByRole('button').click();
            await page.getByRole('button', { name: 'Download log' }).click();
            await waitForLoaders(page);

            const downloadPromise = page.waitForEvent('download', { timeout: 300_000 });
            const download = await downloadPromise;

            await download.saveAs('snapshots/MigrationTest.spec.ts/migration-log.text');

            const logStream = await download.createReadStream();
            const buffers = [];

            for await (const data of logStream) {
                buffers.push(data);
            }

            const finalBuffer = Buffer.concat(buffers);
            let logString = finalBuffer.toString();

            // replace timestamps
            logString = logString.replaceAll(
                /[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{2,4}\s[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}\sUTC/g,
                'TimestampXXX',
            );

            // replace ids
            logString = logString.replaceAll(/(\s|")(0[0-9a-fA-F]+)/g, '$1XXX');

            // clean up media logs
            logString = logString.replaceAll(/(\[warning] SWAG_MIGRATION_CANNOT_GET_).+\n.+\n.+/g, '$1XXX\nXXX\nXXX');

            expect.soft(logString).toMatchSnapshot('migration-log-sw5.txt');
        });
    });
});
