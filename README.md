# Installation

As there is currently no way to  add custom routes, you need to adjust `development/config/routes/platform.yaml`.
Add following lines:
```yaml
migration:
    resource: ../custom/plugins/SwagMigrationNext/Controller/
    type: annotation
```
