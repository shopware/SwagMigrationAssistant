import { test, expect } from '../../fixtures/AcceptanceTest';

const LOADING_TIMEOUT = 30_000;

test.describe('Visual Regression Tests @visual', () => {
    test.describe.configure({
        retries: 0,
        timeout: 120_000,
    });

    const dynamicElementSelectors = [
        '.sw-version__info', // Shopware version
        '.sw-avatar', // User avatars
        '[class*="timestamp"]', // Timestamps
        '[class*="date"]', // Dates
    ];

    async function waitForStableState(
        page: typeof test extends (args: infer T) => void
            ? T extends { MigrationUser: { page: infer P } }
                ? P
                : never
            : never,
    ) {
        await expect(page.locator('.sw-loader-element')).toHaveCount(0, { timeout: LOADING_TIMEOUT });
        await page.waitForTimeout(500);
    }

    test('Migration Assistant - Main page (no connection)', async ({ MigrationUser }) => {
        const page = MigrationUser.page;

        await page.goto('/admin');
        await waitForStableState(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForStableState(page);

        await expect(page).toHaveScreenshot('main-page-no-connection.png', {
            mask: dynamicElementSelectors.map((selector) => page.locator(selector)),
            fullPage: false,
        });
    });

    test('Migration Assistant - Wizard introduction', async ({ MigrationUser }) => {
        const page = MigrationUser.page;

        await page.goto('/admin');
        await waitForStableState(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForStableState(page);

        await page.getByRole('button', { name: 'Create initial connection' }).click();
        await waitForStableState(page);

        await expect(page).toHaveScreenshot('wizard-introduction.png', {
            mask: dynamicElementSelectors.map((selector) => page.locator(selector)),
            fullPage: false,
        });
    });

    test('Migration Assistant - Wizard profile selection', async ({ MigrationUser }) => {
        const page = MigrationUser.page;

        await page.goto('/admin');
        await waitForStableState(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForStableState(page);
        await page.getByRole('button', { name: 'Create initial connection' }).click();
        await waitForStableState(page);

        await page.getByRole('button', { name: 'Start', exact: true }).click();
        await waitForStableState(page);

        await expect(page).toHaveScreenshot('wizard-profile-selection.png', {
            mask: dynamicElementSelectors.map((selector) => page.locator(selector)),
            fullPage: false,
        });
    });

    test('Migration Assistant - Wizard connection setup', async ({ MigrationUser }) => {
        const page = MigrationUser.page;

        await page.goto('/admin');
        await waitForStableState(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForStableState(page);
        await page.getByRole('button', { name: 'Create initial connection' }).click();
        await waitForStableState(page);
        await page.getByRole('button', { name: 'Start', exact: true }).click();
        await waitForStableState(page);
        await page.getByRole('button', { name: 'Continue' }).click();
        await waitForStableState(page);

        await expect(page).toHaveScreenshot('wizard-connection-setup.png', {
            mask: dynamicElementSelectors.map((selector) => page.locator(selector)),
            fullPage: false,
        });
    });
});

test.describe('Component Visual Tests @visual @components', () => {
    test.describe.configure({
        retries: 0,
        timeout: 60_000,
    });

    test('Dashboard card appearance', async ({ MigrationUser }) => {
        const page = MigrationUser.page;

        await page.goto('/admin');
        await expect(page.locator('.sw-loader-element')).toHaveCount(0, { timeout: LOADING_TIMEOUT });

        const dashboardCard = page.locator('.swag-migration-dashboard-card');

        if (await dashboardCard.isVisible()) {
            await expect(dashboardCard).toHaveScreenshot('dashboard-card.png');
        }
    });
});
