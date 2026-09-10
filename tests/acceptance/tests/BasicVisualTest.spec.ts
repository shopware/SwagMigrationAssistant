import { test, expect } from '@fixtures/AcceptanceTest';
import { expectSnapshot, waitForLoaders } from '@fixtures/TestHelpers';

test.describe('Visual Regression Tests @visual', () => {
    test.describe.configure({
        timeout: 120_000,
    });

    test('Main page (no connection)', async ({ ShopAdmin }) => {
        const page = ShopAdmin.page;

        await page.goto('/admin');
        await waitForLoaders(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForLoaders(page);

        await expectSnapshot(page, 'main-page-general-no-connection.png');

        await page.getByTitle('Data selection').click();
        await waitForLoaders(page);

        await expectSnapshot(page, 'main-page-data-selection-empty.png');
    });

    test('Main page (with connection)', async ({ ShopAdmin, MigrationConnection: _ }) => {
        const page = ShopAdmin.page;

        await page.goto('/admin');
        await waitForLoaders(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForLoaders(page);

        await expectSnapshot(page, 'main-page-general-with-connection.png');

        await page.getByTitle('Data selection').click();
        await waitForLoaders(page);

        await expectSnapshot(page, 'main-page-data-selection.png');
    });

    test('Connection wizard (local, happy path)', async ({ ShopAdmin, DatabaseCredentials }) => {
        const page = ShopAdmin.page;

        await page.goto('/admin');
        await waitForLoaders(page);

        await page.getByRole('button', { name: 'Open Migration Assistant' }).click();
        await waitForLoaders(page);

        await page.getByRole('button', { name: 'Create initial connection' }).click();
        await waitForLoaders(page);

        await expectSnapshot(page, 'connection-wizard-introduction.png');

        await page.getByRole('button', { name: 'Start' }).click();

        await expectSnapshot(page, 'connection-wizard-profiles.png');

        await page.getByRole('button', { name: 'Continue' }).click();

        await expectSnapshot(page, 'connection-wizard-create.png');

        await page.getByPlaceholder('Enter name').fill('shopware55local');

        await page.locator('.swag-migration-wizard-page-create-profile__gateway-select').getByText('API').click();
        await page.locator('.sw-select-result-list__content').getByText('Local database').click();

        await page.getByRole('button', { name: 'Establish connection' }).click();
        await waitForLoaders(page);

        // lose focus of host input
        await page.locator('.sw-modal__title').click();

        await expectSnapshot(page, 'connection-wizard-establish-local.png');

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

        await page.locator('.swag-migration-shop-information__actions-context-menu').click();
        await page.getByRole('button', { name: 'Truncate migration' }).click();
        await page.getByRole('button', { name: 'Archive' }).click();

        await expectSnapshot(page, 'connection-wizard-truncation.png', { clip: true });

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
        await expectSnapshot(page, 'dashboard-card.png');
    });
});
