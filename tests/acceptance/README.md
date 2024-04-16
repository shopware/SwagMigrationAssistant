## Setup

Navigate to this directory if you haven't yet.

```
cd tests/acceptance
```

Install the project dependencies.

```
npm install
```

Also make sure this plugin is installed.

## Running Tests

### Running all SwagMigrationAssistant tests

Navigate to `[platform-repo]/tests/acceptance` and run:

```
npx playwright test --project=swag-migration-assistant
```

If that doesn't work, try the plugin folder name
```
npx playwright test --project=SwagMigrationAssistant
```

### Running all tests incl. SwagMigrationAssistant

Navigate to `[platform-repo]/tests/acceptance` and run:

```
npx playwright test
```
