/* eslint-disable */
/**
 * @deprecated tag:v16.0.0 - With Shopware v6.8.0 - `translation.init.ts` will be removed to use automatic language loading with language layer support
 */
import deMigrationSnippets from '../module/swag-migration/snippet/de.json';
import enMigrationSnippets from '../module/swag-migration/snippet/en.json';

Shopware.Locale.extend('de-DE', deMigrationSnippets);
Shopware.Locale.extend('en-GB', enMigrationSnippets);
