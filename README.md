# SwagMigrationAssistant

The Shopware Migration Assistant establishes a connection between a data source and Shopware 6 and guides you step by step through the migration process.

It supports migrating numerous datasets (products, manufacturers, customers, …) and updating them at any time. Before a migration starts, the assistant runs a data check and supports creating “mappings” to resolve missing/unassignable data (for example: assign missing manufacturers to a default manufacturer) to avoid data loss.

## Overview

- [Documentation](#documentation)
- [Shopware 5](#shopware-5-migration-connector)
- [Requirements](#requirements)
- [Installation](#installation-shopware-6-project)
- [Developer setup](#developer-setup)
- [Common workflows](#common-workflows)
- [Release notes / upgrades](#release-notes--upgrades)
- [License](#license)

## Documentation

- **User documentation**: [Shopware Migration docs](https://docs.shopware.com/en/migration-en)
- **Developer documentation**: [Migration Assistant developer docs](https://developer.shopware.com/docs/products/extensions/migration-assistant/)

## Shopware 5: Migration Connector

When migrating from Shopware 5, you can migrate locally or connect via the “Migration Connector” plugin from the Shopware Store:

- **Migration Connector**: [Shopware Store listing](https://store.shopware.com/de/swag226607479310f/migration-connector.html)

The connector provides API endpoints so Shopware 6 can establish a secure connection to the Shopware 5 shop. Keep it enabled as long as you need updates/delta migrations.

## Requirements

- **Shopware**: `shopware/core` (see version here [`composer.json`](composer.json))
- **Node.js / npm**: required for administration and JS tooling (lint/unit/acceptance)
- **MySQL client**: required for importing Shopware 5 fixture data (optional)

## Installation (Shopware 6 project)

Expected path (relative to your Shopware 6 project root) for the plugin:

`custom/plugins/SwagMigrationAssistant`

From the shopware root directory:

```bash
bin/console plugin:refresh
bin/console plugin:install -a -c SwagMigrationAssistant
```

Alternatively, use the provided shortcut:

```bash
composer setup
```

## Developer setup

- **Install JS dependencies** (administration + Jest + Playwright project):

```bash
composer npm:init
```

- **Install git pre-commit hook** (optional):

```bash
./bin/setup.sh
```

## Common workflows

### Linting & formatting

Run everything (PHP + admin):

```bash
composer lint
```

Run individual parts:

```bash
composer ecs
composer phpstan
composer phpunit
composer admin:lint # eslint
composer admin:format # prettier
```

### Tests

- **PHPUnit**:

```bash
composer phpunit
```

- **Administration unit tests (Jest)**:

```bash
composer admin:unit
```

- **Acceptance tests (Playwright)**:

```bash
composer admin:acceptance
```

More details (including Playwright install steps) can be found in the [acceptance tests README](tests/acceptance/README.md).

### Updating visual regression snapshots

Visual regression tests compare screenshots against baseline images. When UI changes are intentional, update the snapshots via CI to ensure consistency across environments:

1. Go to **Actions** > **Acceptance** workflow
2. Click **Run workflow**
3. Check **Update snapshots**
4. Run the workflow and wait for completion
5. Download the `visual-snapshots-trunk` artifact
6. Extract and replace `tests/acceptance/snapshots/` with the downloaded files
7. Commit the updated snapshots to your branch

### Import Shopware 5 fixture database (optional)

This imports `tests/_fixtures/database/shopware55.sql` into the database configured in your Shopware root `.env` via `DATABASE_URL`.

```bash
composer install5db
```

## Release notes / upgrades

- **Changelog**: [`CHANGELOG.md`](CHANGELOG.md)
- **Breaking changes**: [`UPGRADE.md`](UPGRADE.md)

## License

`MIT` See [`LICENSE`](LICENSE).
