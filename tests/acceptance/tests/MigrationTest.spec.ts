/* eslint-disable playwright/no-conditional-in-test */
/* eslint-disable playwright/no-conditional-expect */
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

            const identifyFieldType = async () => {
                if ((await page.locator('.swag-migration-error-resolution-field-relation').count()) > 0) {
                    return 'relation';
                }

                if ((await page.locator('.swag-migration-error-resolution-field-unhandled__banner').count()) > 0) {
                    return 'textarea';
                }

                if ((await page.locator('.sw-migration-error-resolution-field__text').count()) > 0) {
                    return 'text';
                }

                if ((await page.locator('.sw-migration-error-resolution-field__number').count()) > 0) {
                    return 'number';
                }

                return null;
            };

            const processRelationField = async () => {
                await page.locator('.swag-migration-error-resolution-field-relation .sw-select__selection').click();
                await page.locator('.sw-select-result').first().click();
                await page.locator('.sw-modal__title').first().click();
            };

            const processTextField = async () => {
                const input = page.locator('.sw-migration-error-resolution-field__text input');
                await input.first().waitFor();
                await input.fill('Fixed via migration assistant');
            };

            const processNumberField = async () => {
                const input = page.locator('.sw-migration-error-resolution-field__number input');
                await input.first().waitFor();
                await input.fill('42');
            };

            const processTextareaField = async () => {
                const input = page.locator('.sw-code-editor textarea');
                await input.first().waitFor();
                await input.clear();
                await input.fill('019c1db803ad709ebdb37d5f67487a44');
            };

            const processLogEntry = async (index: number) => {
                await logs.nth(index).getByRole('button', { name: 'Open actions menu' }).click();
                await page.getByRole('button', { name: 'Edit' }).click();
                await waitForLoaders(page);

                await page.locator('.mt-field--checkbox').first().click();
                await page.getByRole('button', { name: /Select all \(\d+\)/ }).click();
                await waitForLoaders(page);

                const type = await identifyFieldType();
                expect(type).not.toBeNull();

                if (type === 'relation') {
                    await processRelationField();
                    return;
                }

                if (type === 'textarea') {
                    await processTextareaField();
                    return;
                }

                if (type === 'text') {
                    await processTextField();
                    return;
                }

                if (type === 'number') {
                    await processNumberField();
                }
            };

            const logCount = await logs.count();

            for (let i = 0; i < logCount; i++) {
                await processLogEntry(i);

                if (i === 0) {
                    await expect(page).toHaveScreenshot('error-resolution-log-detail-unfixed.png', { mask });
                }

                await page.getByRole('button', { name: 'Apply changes' }).click();
                await waitForLoaders(page);

                if (i === 0) {
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

            await page.locator('.swag-migration-error-resolution-step__header-buttons-continue').click();
            await waitForLoaders(page);

            await page.locator('.swag-migration-error-resolution-step__continue-modal-confirm').click();
            await waitForLoaders(page);
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

            await page.locator('.sw-data-grid__body .sw-data-grid__cell--actions').getByRole('button').click();
            await waitForLoaders(page);

            await page.locator('.sw-context-menu__content .sw-context-menu-item').first().click();
            await waitForLoaders(page);

            await expect(page).toHaveScreenshot('migration-history-details-modal.png', { mask });
        });

        await test.step('Verify migration logs', async () => {
            await page.locator('.sw-modal__close').click();
            await waitForLoaders(page);

            await page.locator('.sw-data-grid__body .sw-data-grid__cell--actions').getByRole('button').click();
            await waitForLoaders(page);

            const downloadPromise = page.waitForEvent('download', { timeout: 300_000 });

            await page.locator('.sw-context-menu__content .sw-context-menu-item').last().click();
            const download = await downloadPromise;
            await waitForLoaders(page);

            const logStream = await download.createReadStream();
            const buffers = [];

            for await (const data of logStream) {
                buffers.push(data);
            }

            const finalBuffer = Buffer.concat(buffers);
            let logString = finalBuffer.toString();

            // remove media warning logs
            logString = logString.replaceAll(
                /----- Log Entry #[0-9]+ -----\nID: [0-9a-f]{32}\nLevel: warning\nCode: SWAG_MIGRATION_MEDIA_FILE_MISSING(.|\n)+?\n\n/g,
                '',
            );

            // replace timestamps
            logString = logString.replaceAll(/[0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2}\sUTC/g, '[timestamp]');

            // replace domain
            logString = logString.replaceAll(/"sourceSystemDomain":\s".*"/g, '"sourceSystemDomain": "[domain]"');

            // replace ids
            logString = logString.replaceAll(/[0-9a-f]{32}/g, '[uuid]');

            expect(logString).toMatchSnapshot('migration-log-sw5.txt');
        });
    });
});
