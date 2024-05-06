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

### Running tests and update snapshots / screenshots
```
npx playwright test --project=swag-migration-assistant --update-snapshots
```
you might need to rename the snapshot files, so they end in `-linux.txt`,
otherwise the CI pipeline doesn't recognise them.
Also, the CI pipeline might still produce a slightly different log file than your local machine,
in that case you could also copy the actual log from the pipeline
(you can download it inside the playwright trace)
