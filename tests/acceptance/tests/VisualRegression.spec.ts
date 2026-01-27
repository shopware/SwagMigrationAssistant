import type { Page } from 'playwright-core';
import { test, expect } from '../fixtures/AcceptanceTest';

const LOADING_TIMEOUT = 30_000;

const dynamicElementSelectors = [
    '.sw-version__info',
    '.sw-avatar',
    '.sw-admin-menu__user-name',
    '[class*="timestamp"]',
    '[class*="date"]',
];

function getMask(page: Page) {
    return dynamicElementSelectors.map((selector) => page.locator(selector));
}

async function waitForLoaders(page: Page) {
    await expect(page.locator('.sw-loader-element')).toHaveCount(0, { timeout: LOADING_TIMEOUT });
}

test.describe('Visual Regression Tests @visual', () => {
    test.describe.configure({
        retries: 0,
        timeout: 120_000,
    });

    test('Main page (no connection)', async ({ ShopAdmin }) => {
        const page = ShopAdmin.page;

        await page.goto('/admin');
        await waitForLoaders(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('main-page-general-no-connection.png', {
            mask: getMask(page),
        });

        await page.getByTitle('Data selection').click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('main-page-data-selection-empty.png', {
            mask: getMask(page),
        });
    });

    test('Connection wizard (local, happy path)', async ({ ShopAdmin, DatabaseCredentials }) => {
        const page = ShopAdmin.page;
        const mask = getMask(page);

        await page.goto('/admin');
        await waitForLoaders(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForLoaders(page);

        await page.getByRole('button', { name: 'Create initial connection' }).click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('connection-wizard-introduction.png', {
            mask,
        });

        await page.getByRole('button', { name: 'Start' }).click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('connection-wizard-profiles.png', {
            mask,
        });

        await page.getByRole('button', { name: 'Continue' }).click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('connection-wizard-create.png', {
            mask,
        });

        await page.getByPlaceholder('Enter name').fill('shopware55local');

        await page.getByText('API').click();
        await page.getByText('Local database').click();

        await page.getByRole('button', { name: 'Establish connection' }).click();
        await waitForLoaders(page);

        // lose focus of host input
        await page.getByText('Migration').click();

        await expect(page).toHaveScreenshot('connection-wizard-establish-local.png', {
            mask,
        });

        await page.getByPlaceholder('Enter host').fill(DatabaseCredentials.host);
        await page.getByLabel('Port').fill(DatabaseCredentials.port);
        await page.getByPlaceholder('Enter username').fill(DatabaseCredentials.user);
        await page.getByPlaceholder('Enter password').fill(DatabaseCredentials.password);
        await page.getByPlaceholder('Enter name').fill(DatabaseCredentials.database);
        await page.getByPlaceholder('Enter installation root').fill('/tmp');

        await page.getByRole('button', { name: 'Connect' }).click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('connection-wizard-success.png', {
            mask,
        });

        await page.getByRole('button', { name: 'Done' }).click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('main-page-general-with-connection.png', {
            mask,
        });

        await page.getByTitle('Data selection').click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('main-page-data-selection.png', {
            mask: getMask(page),
        });
    });
});

test.describe('Component Visual Tests @visual @components', () => {
    test.describe.configure({
        retries: 0,
        timeout: 60_000,
    });

    test('Dashboard card appearance', async ({ ShopAdmin }) => {
        const page = ShopAdmin.page;

        await page.goto('/admin');
        await waitForLoaders(page);

        const dashboardCard = page.locator('.swag-migration-dashboard-card');

        await expect(dashboardCard).toBeVisible();
        await expect(dashboardCard).toHaveScreenshot('dashboard-card.png');
    });
});
