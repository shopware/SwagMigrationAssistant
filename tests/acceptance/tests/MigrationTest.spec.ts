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
            await expect(page).toHaveScreenshot('data-selection-assigment-with-errors.png', {
                mask,
            });
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

            await expect(page).toHaveScreenshot('data-selection-assigment-without-errors.png', {
                mask,
            });
        });

        await test.step('Start migration', async () => {
            await page.getByRole('button', { name: 'Start migration' }).click();
            await waitForLoaders(page);

            await page.getByRole('button', { name: 'Continue anyway' }).click();
            await waitForLoaders(page);

            await expect(page).toHaveScreenshot('migration-process-started.png', {
                mask,
            });

            const step = page.locator('.sw-step-display > .sw-step-item').first();
            await expect(step).toHaveClass(/sw-step-item--success/, { timeout: 300_000 });

            await waitForLoaders(page);
            await expect(page.getByText('Review and resolve migration errors')).toBeVisible({ timeout: 300_000 });
        });

        await test.step('Error resolution', async () => {
            const restoreViewport = await withLargerViewport(page);
            await expect(page).toHaveScreenshot('error-resolution-log-groups.png', {
                mask,
            });
            await restoreViewport();

            await page.locator('.sw-data-grid__cell--actions').nth(2).getByRole('button').click();
            await page.getByRole('button', { name: 'Edit' }).click();
            await waitForLoaders(page);

            await page.locator('.mt-field--checkbox').first().click();
            await page.locator('.swag-migration-error-resolution-field-relation .sw-select__selection').click();
            await page.locator('.sw-select-result').first().click();
            await page.locator('.sw-modal__title').first().click();

            await expect(page).toHaveScreenshot('error-resolution-log-detail.png', {
                mask,
            });

            await page.getByRole('button', { name: 'Apply changes' }).click();
            await waitForLoaders(page);

            await page.locator('.sw-modal__close').click();
            await waitForLoaders(page);
        });
    });
});
