import { test, expect } from '../fixtures/AcceptanceTest';
import { getMask, waitForLoaders } from '../fixtures/TestHelpers';

test.describe('Visual Regression Tests @visual', () => {
    test.describe.configure({
        timeout: 120_000,
    });

    test('Main page (no connection)', async ({ ShopAdmin }) => {
        const page = ShopAdmin.page;
        const mask = getMask(page);

        await page.goto('/admin');
        await waitForLoaders(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('main-page-general-no-connection.png', { mask });

        await page.getByTitle('Data selection').click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('main-page-data-selection-empty.png', { mask });
    });

    test('Main page (with connection)', async ({ ShopAdmin, MigrationConnection: _ }) => {
        const page = ShopAdmin.page;
        const mask = getMask(page);

        await page.goto('/admin');
        await waitForLoaders(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('main-page-general-with-connection.png', { mask });

        await page.getByTitle('Data selection').click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('main-page-data-selection.png', { mask });
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

        await expect(page).toHaveScreenshot('connection-wizard-introduction.png', { mask });

        await page.getByRole('button', { name: 'Start' }).click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('connection-wizard-profiles.png', { mask });

        await page.getByRole('button', { name: 'Continue' }).click();
        await waitForLoaders(page);

        await expect(page).toHaveScreenshot('connection-wizard-create.png', { mask });

        await page.getByPlaceholder('Enter name').fill('shopware55local');

        await page.getByText('API').click();
        await page.getByText('Local database').click();

        await page.getByRole('button', { name: 'Establish connection' }).click();
        await waitForLoaders(page);

        // lose focus of host input
        await page.getByText('Migration').click();

        await expect(page).toHaveScreenshot('connection-wizard-establish-local.png', { mask });

        await page.getByPlaceholder('Enter host').fill(DatabaseCredentials.host);
        await page.getByLabel('Port').fill(DatabaseCredentials.port);
        await page.getByPlaceholder('Enter username').fill(DatabaseCredentials.user);
        await page.getByPlaceholder('Enter password').fill(DatabaseCredentials.password);
        await page.getByPlaceholder('Enter name').fill(DatabaseCredentials.database);
        await page.getByPlaceholder('Enter installation root').fill('/tmp');

        await page.getByRole('button', { name: 'Connect' }).click();
        await waitForLoaders(page);

        await page.getByRole('button', { name: 'Done' }).click();
        await waitForLoaders(page);

        await page.getByTestId('mt-icon__solid-ellipsis-h-s').click();
        await page.getByRole('button', { name: 'Truncate migration' }).click();
        await page.getByRole('button', { name: 'Archive' }).click();

        await expect(page).toHaveScreenshot('connection-wizard-truncation.png', { mask });

        await waitForLoaders(page, 300_000); // wait for truncation

        await page.reload();
        await waitForLoaders(page);

        await expect(page.getByRole('button', { name: 'Create initial connection' })).toBeVisible();
    });
});

test.describe('Component Visual Tests @visual @components', () => {
    test.describe.configure({
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
