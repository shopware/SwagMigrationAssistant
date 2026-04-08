# Migration Proof

This package runs an end-to-end migration proof between a Shopware 5 source system and a Shopware 6 target system.

It boots both systems with Docker, installs the required migration plugins, starts a migration through the Shopware 6 admin API, and verifies the result with grouped logs and entity-count assertions.

## What It Does

- Starts a source Shopware 5 container from `dockware/dev`.
- Starts a target Shopware 6 container from `dockware/shopware`.
- Installs and activates `SwagMigrationConnector` in the source container.
- Installs and activates `SwagMigrationAssistant` in the target container.
- Creates a migration connection and runs the migration.
- Verifies grouped logs and migrated entity totals.
- Writes artifacts to `MIGRATION_PROOF_OUTPUT_DIR`.

## Requirements

- Docker with `docker compose`
- Bun

## Local Usage

```bash
cd tests/migration-proof
cp .env.example .env
bun install
bun run verify
bun run proof:dev
```

Useful commands:

- `bun run docker:up`
- `bun run docker:down`
- `bun run proof:dev`
- `bun run proof:ci`

## Environment Variables

The proof reads its runtime configuration from `.env` locally and from GitHub Actions in CI.

### Source System

| Variable | Required | Default | Purpose |
| --- | --- | --- | --- |
| `MIGRATION_PROOF_SOURCE_URL` | no | `http://127.0.0.1:8081` | Source URL reachable from the host. |
| `MIGRATION_PROOF_SOURCE_DOCKER_URL` | no | `http://source` | Source URL reachable from the target container. |
| `MIGRATION_PROOF_SOURCE_HOST_PORT` | no | `8081` | Host port mapped to the source container. |
| `MIGRATION_PROOF_SOURCE_CONTAINER` | no | `migration-proof-source` | Docker container name for the source system. |
| `MIGRATION_PROOF_SOURCE_DB_NAME` | no | `shopware` | Source database name used for verification queries. |
| `MIGRATION_PROOF_SOURCE_API_USERNAME` | no | `migration-proof-api` | Source API username bootstrapped in the fixture. |
| `MIGRATION_PROOF_SOURCE_API_PASSWORD` | no | `migration-proof-api-password` | Source API password bootstrapped in the fixture. |
| `MIGRATION_PROOF_SOURCE_API_KEY` | no | `migration-proof-api-key-1234567890abcdef` | Source API key used by the migration connection. Shopware API credentials should use a 40-character key. |
| `MIGRATION_PROOF_SOURCE_CONNECTION_NAME` | no | `migration-proof-shopware-api` | Base name for the generated migration connection. |
| `MIGRATION_PROOF_SOURCE_GATEWAY_NAME` | yes | none | Gateway used for the source connection, usually `api`. |
| `MIGRATION_PROOF_SOURCE_PROFILE_NAME` | yes | none | Source profile, for example `shopware57`. |
| `SOURCE_SHOPWARE_VERSION` | yes | none | Source Dockware image tag, for example `5.7.14`. |
| `SOURCE_PHP_VERSION` | yes | none | PHP version injected into the source Dockware container. |
| `SOURCE_NODE_VERSION` | yes | none | Node version injected into the source Dockware container. |
| `SOURCE_CONNECTOR_ZIP_URL` | no | `https://github.com/shopware/SwagMigrationConnector/archive/refs/tags/2.2.0.zip` | Connector archive installed into the source system. |

### Target System

| Variable | Required | Default | Purpose |
| --- | --- | --- | --- |
| `MIGRATION_PROOF_TARGET_URL` | no | `http://127.0.0.1:8080` | Target URL reachable from the host. |
| `MIGRATION_PROOF_TARGET_HOST_PORT` | no | `8080` | Host port mapped to the target container. |
| `MIGRATION_PROOF_TARGET_CONTAINER` | no | `migration-proof-target` | Docker container name for the target system. |
| `MIGRATION_PROOF_TARGET_ADMIN_USERNAME` | no | `admin` | Target admin username used by the proof client. |
| `MIGRATION_PROOF_TARGET_ADMIN_PASSWORD` | no | `shopware` | Target admin password used by the proof client. |
| `MIGRATION_PROOF_TARGET_PLUGIN_NAME` | no | `SwagMigrationAssistant` | Target plugin installed before the proof starts. |
| `TARGET_SHOPWARE_VERSION` | yes | none | Target Dockware image tag, currently aligned to Shopware 6.7. |
| `TARGET_PHP_VERSION` | yes | none | PHP version injected into the target Dockware container. |
| `TARGET_NODE_VERSION` | yes | none | Node version injected into the target Dockware container. |

### Proof Runtime

| Variable | Required | Default | Purpose |
| --- | --- | --- | --- |
| `MIGRATION_PROOF_OUTPUT_DIR` | no | `./output` locally | Directory for generated artifacts. |
| `MIGRATION_PROOF_BOOTSTRAP` | no | `false` | Skips strict migration-log and entity-total assertions when enabled. |
| `MIGRATION_PROOF_DEBUG` | no | `true` | Enables verbose proof logging. |
| `MIGRATION_PROOF_DATA_SELECTION_IDS` | no | empty | Optional comma-separated list of migration data selections. |

## Source Presets

The table below is the recommended baseline for this package.

It separates official Shopware PHP minimums from the package defaults we recommend for local and CI runs. The official PHP minimums come from the Shopware 5 upgrade guide: [developers.shopware.com/developers-guide/shopware-5-upgrade-guide-for-developers](https://developers.shopware.com/developers-guide/shopware-5-upgrade-guide-for-developers/).

| Profile | `SOURCE_SHOPWARE_VERSION` | Official Shopware PHP floor | Recommended `SOURCE_PHP_VERSION` | Recommended `SOURCE_NODE_VERSION` | Notes |
| --- | --- | --- | --- | --- | --- |
| `shopware54` | `5.4.6` | `5.6.4+` | `7.2` | `12` | Shopware 5.4 still allows PHP 5.6.4+, but PHP 7.x is encouraged. |
| `shopware55` | `5.5.10` | `5.6.4+` | `7.2` | `12` | Shopware 5.5 explicitly recommends PHP 7.2. |
| `shopware56` | `5.6.10` | `7.2+` | `7.3` | `12` | Shopware 5.6 adds support for PHP 7.3. |
| `shopware57` | `5.7.14` | `7.4+` | `7.4` | `12` | This is the default local baseline for this package. |

## CI

The reusable workflow entrypoint is `.github/workflows/action-migration-proof.yml`.

The compatibility matrix entrypoint is `.github/workflows/compatibility.yaml`.
