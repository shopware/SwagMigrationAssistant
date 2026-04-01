# SwagMigrationAssistant Acceptance Tests

Playwright-based acceptance tests for the Migration Assistant plugin.

## Setup

Navigate to the acceptance tests directory:

```bash
cd tests/acceptance
```

Install dependencies:

```bash
npm install
```

Install Playwright browsers (first time only):

```bash
npx playwright install
npx playwright install-deps
```

## Environment Variables

Create a `.env` file in this directory or use the platform's `.env` file (auto-loaded from `../../../../../.env`).

### Required

| Variable | Description |
|----------|-------------|
| `APP_URL` | Base URL of your Shopware instance (e.g., `http://localhost:8000`) |
| `DATABASE_URL` | Database connection string |

### Optional

| Variable | Default | Description |
|----------|---------|-------------|
| `SHOPWARE_ADMIN_USERNAME` | `admin` | Admin username |
| `SHOPWARE_ADMIN_PASSWORD` | `shopware` | Admin password |
| `SHOPWARE_PLAYWRIGHT_IGNORE_HTTPS_ERRORS` | `false` | Set to `true` or `1` to ignore HTTPS errors |

## Running Tests

### Run all tests

```bash
npm test
```

### Run tests with UI mode

```bash
npm run test:ui
```

### Run specific test file

```bash
npx playwright test tests/BasicVisualTest.spec.ts
```

### Run tests by tag

```bash
npx playwright test --grep @migration
```

### Update snapshots

```bash
npx playwright test --update-snapshots
```

## Available Scripts

| Script | Description |
|--------|-------------|
| `npm test` | Run all tests |
| `npm run test:ui` | Run tests in UI mode (port 3000) |
| `npm run lint` | Check for linting errors |
| `npm run lint:fix` | Fix linting errors |
| `npm run format` | Check code formatting |
| `npm run format:fix` | Fix code formatting |
| `npm run types` | Type-check TypeScript files |

## Configuration

### Viewport

Default viewport is `1440x1080`. For screenshots requiring more height, use the `withLargerViewport` helper:

```typescript
import { withLargerViewport } from '@fixtures/TestHelpers';

const restoreViewport = await withLargerViewport(page);
await expect(page).toHaveScreenshot('my-screenshot.png');
await restoreViewport();
```

## Snapshots

Snapshots are stored per platform (`darwin`/`linux`). CI runs on Linux, so ensure Linux snapshots exist.

### Updating snapshots

Visual regression tests compare screenshots against baseline images. When UI changes are intentional, update the snapshots via CI to ensure consistency across environments:

1. Go to **Actions** > **Acceptance** workflow
2. Click **Run workflow**
3. Check **Update snapshots**
4. Run the workflow and wait for completion
5. Download the `visual-snapshots-trunk` artifact
6. Extract and replace `tests/acceptance/snapshots/` with the downloaded files
7. Commit the updated snapshots to your branch
