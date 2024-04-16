# Shopware Migration Assistant (SwagMigrationAssistant)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Description

The Shopware Migration Assistant establishes a connection between a data source (e.g. Shopware 5 shop) and Shopware 6 and guides you step by step through the migration process.

You can migrate numerous datasets – e.g. products, manufacturers, customers, etc. – from your existing shop system to Shopware 6 and update them at any time.

The assistant also makes it possible to connect two systems in order to make the transition as easy as possible.



Once the connection has been established, it saves automatically so that it can be accessed at any time. After the first complete migration, individual datasets can also be migrated or updated as needed.



Before the migration takes place, the assistant performs a data check to determine missing or unassignable datasets. Within the scope of this check it may be necessary to create so-called “mapping”, which replaces any missing data with an assignment (e.g. missing manufacturer data automatically reverts to the default manufacturer) – this ensures you can quickly migrate without losing any data.



## Info for Shopware 5 users:

In migrating your Shopware 5 shop, you can either perform the migration locally or use the “Migration Connector” plugin [Link to the plugin](https://store.shopware.com/search?sSearch=Swag226607479310).



The Migration Connector provides API endpoints that allow Shopware 6 to establish a secure data connection with the active Shopware 5 shop. As long as you are using Shopware 6, you should leave the plugin enabled. This is the only way to update the data at any time.



## Get started with the Migration Assistant:



- Download the Shopware Migration Assistant from the Community Store
- Activate the plugin in your Shopware 6 installation – this can be done in the Plugin Manager under “Purchases”. Alternatively, you can use the Plugin Manager to upload the plugin ZIP file.
- The Migration Assistant will then guide you through the migration process.

## Documentation

If you need further information. You can have a look at our [user documentation](https://docs.shopware.com/en/migration-en) and our [developer documentation](https://developer.shopware.com/docs/products/extensions/migration-assistant/)
