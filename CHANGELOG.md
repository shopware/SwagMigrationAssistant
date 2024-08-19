# 13.2.0
- MIG-1035 - Changed the premapping assignment in the administration, which should fix issues with assignment on later pagination pages and changes getting lost with newly selected data
- MIG-1042 - Adding a notice to clear the cache when the Shopware 5 Connector can't be found.
- MIG-1045 - Fix displayed percentage status while reading data
- MIG-1046 - Fixed an issue related to the validation of json fields for a migration run, which could prevent starting a migration in the latest shopware version

# 13.1.0
- MIG-981 - Media migration now uses the system's temporary directory to download files.
- MIG-1016 - Improves the warnings for different default currency and default language in the data selection.
- MIG-1016 - Added new block `{% block swag_migration_confirm_warning_alert %}` in `swag-migration/component/card/swag-migration-confirm-warning/swag-migration-confirm-warning.html.twig`.
- MIG-1037 - Fixes a rare issue that in certain situations not all entities are migrated (some were skipped). Was detected during translations of SW5.
 
# 13.0.0
- MIG-945 - [BREAKING] Changed method name `getMedia` to `setMedia` in `SwagMigrationAssistant\Profile\Shopware\Converter\PropertyGroupOptionConverter`
- MIG-945 - [BREAKING] Removed cli command `migration:migrate` use `migration:start` instead
- MIG-945 - [BREAKING] Changed method `writePremapping` of `SwagMigrationAssistant\Controller\PremappingController`
    - Changed return type from `JsonResponse` to `Response`
    - Removed parameter `runUuid`
- MIG-945 - [BREAKING] Removed method `finishMigration` of `SwagMigrationAssistant\Controller\StatusController`
- MIG-945 - [BREAKING] Changed type of `permapping` field of `SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition` from `JsonField` to new `PremappingField`
- MIG-945 - [BREAKING] Changed type of `progress` field of `SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition` from `JsonField` to new `MigrationProgressField`
- MIG-945 - [BREAKING] Changed return type of `getProgress` of `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity` from `?array` to `?MigrationProgress`
- MIG-945 - [BREAKING] Changed parameter type of `setProgress` of `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity` from `array` to `MigrationProgress`
- MIG-945 - [BREAKING] Changed return type of `writeData` of `SwagMigrationAssistant\Migration\DataWriter` from `void` to `int`
- MIG-945 - [BREAKING] Changed return type of `writeData` of `SwagMigrationAssistant\Migration\DataWriterInterface` from `void` to `int`
- MIG-945 - [BREAKING] Removed parameter `fileChunkByteSize` of method `process` of `SwagMigrationAssistant\Migration\Media\Processor\HttpDonwloadServiceBase`
- MIG-945 - [BREAKING] Removed parameter `fileChunkByteSize` of method `process` of `SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface`
- MIG-945 - [BREAKING] Removed parameter `fileChunkByteSize` of method `processMediaFiles` of `SwagMigrationAssistant\Migration\Service\MediaFileProcessorService`
- MIG-945 - [BREAKING] Removed parameter `fileChunkByteSize` of method `processMediaFiles` of `SwagMigrationAssistant\Migration\Service\MediaFileProcessorServiceInterface`
- MIG-945 - [BREAKING] Removed parameter `fileChunkByteSize` of method `process` of `SwagMigrationAssistant\Profile\Shopware\Media\LocalMediaProcessor`
- MIG-945 - [BREAKING] Removed parameter `fileChunkByteSize` of method `process` of `SwagMigrationAssistant\Profile\Shopware\Media\LocalOrderDocumentProcessor`
- MIG-945 - [BREAKING] Removed parameter `fileChunkByteSize` of method `process` of `SwagMigrationAssistant\Profile\Shopware\Media\LocalProductDownloadProcessor`
- MIG-945 - [BREAKING] Renamed parameter `context` to `migrationContext` of method `getProcessor` of `SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface`
- MIG-945 - [BREAKING] Renamed parameter `context` to `migrationContext` of method `getProcessor` of `SwagMigrationAssistant\Profile\Shopware6\Media\HttpOrderDocumentGenerationService`
- MIG-945 - [BREAKING] Removed property `fileChunkByteSize` of `SwagMigrationAssistant\Migration\MessageQueue\MessageProcessMediaMessage`
- MIG-945 - [BREAKING] Removed property `runRepo` of `SwagMigrationAssistant\Migration\Service\PremappingService`
- MIG-945 - [BREAKING] Changed `SwagMigrationAssistant\Migration\Service\PremappingServiceInterface` / `SwagMigrationAssistant\Migration\Service\PremappingService`
    - Removed parameter `run` of method `generatePremapping`
    - Added parameter `dataSelectionIds` to method `generatePremapping`
- MIG-945 - [BREAKING] Added new constructor parameters `generalSettingRepository` and `migrationConnectionRepository` to `SwagMigrationAssistant\Migration\MigrationContextFactory`
- MIG-945 - [BREAKING] Added new method `createBySelectedConnection` to interface `SwagMigrationAssistant\Migration\MigrationContextFactoryInterface`
- MIG-945 - [BREAKING] Removed class/interface/struct:
    - `SwagMigrationAssistant\Profile\Shopware\Exception\LocalReaderNotFoundException` use domain exception instead `MigrationException::readerNotFound`
    - `SwagMigrationAssistant\Profile\Shopware\Exception\PluginNotInstalledException` use domain exception instead `MigrationShopwareProfileException::pluginNotInstalled`
    - `SwagMigrationAssistant\Controller\MigrationController`
    - `SwagMigrationAssistant\Migration\Service\MigrationProgressServiceInterface`
    - `SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenStruct`
    - `SwagMigrationAssistant\Exception\ProcessorNotFoundException` use domain exception instead `MigrationException::processorNotFound`
    - `SwagMigrationAssistant\Exception\EntityNotExistsException` use domain exception instead `MigrationException::entityNotExists`
    - `SwagMigrationAssistant\Exception\GatewayNotFoundException` use domain exception instead `MigrationException::gatewayNotFound`
    - `SwagMigrationAssistant\Exception\InvalidConnectionAuthenticationException` use domain exception instead `MigrationException::invalidConnectionAuthentication`
    - `SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException` use domain exception instead `MigrationException::migrationContextPropertyMissing`
    - `SwagMigrationAssistant\Exception\MigrationIsRunningException` use domain exception instead `MigrationException::migrationIsAlreadyRunning`
    - `SwagMigrationAssistant\Exception\MigrationRunUndefinedStatusException` use domain exception instead `MigrationException::undefinedRunStatus`
    - `SwagMigrationAssistant\Exception\MigrationWorkloadPropertyMissingException` use domain exception instead `MigrationException::undefinedRunStatus`
    - `SwagMigrationAssistant\Exception\NoFileSystemPermissionsException` use domain exception instead `MigrationException::noFileSystemPermissions`
    - `SwagMigrationAssistant\Exception\ProfileNotFoundException` use domain exception instead `MigrationException::profileNotFound`
    - `SwagMigrationAssistant\Exception\ReaderNotFoundException` use domain exception instead `MigrationException::readerNotFound`
    - `SwagMigrationAssistant\Exception\ReaderNotFoundException` use domain exception instead `MigrationException::requestCertificateInvalid`
    - `SwagMigrationAssistant\Exception\SslRequiredException` use domain exception instead `MigrationException::sslRequired`
    - `SwagMigrationAssistant\Migration\Service\ProgressState`
    - `SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService`
- MIG-945 - [BREAKING] Make following classes / methods internal:
    - `SwagMigrationAssistant\Migration\MessageQueue\Handler\CleanupMigrationHandler`
    - `SwagMigrationAssistant\Migration\MessageQueue\Handler\ProcessMediaHandler`
    - `SwagMigrationAssistant\Migration\Service\MigrationProgressService`
- MIG-945 - [BREAKING] Changed `SwagMigrationAssistant\Migration\Run\RunService` / `SwagMigrationAssistant\Migration\Run\RunServiceInterface`
    - Removed properties `accessTokenService`, `migrationDataRepository`, `mediaFileRepository`, `indexer`, `cache`
    - Removed methods `takeoverMigration`, `calculateWriteProgress`, `calculateMediaFilesProgress`, `calculateCurrentTotals`, `finishMigration`
    - Removed parameter `abortMigration` of method `abortMigration`
- MIG-945 - [BREAKING] Removed parameter `migrationContext` from method `setNumberRangeSalesChannels` of `SwagMigrationAssistant\Profile\Shopware\Converter\NumberRangeConverter`
- MIG-945 - [BREAKING] Removed parameter `migrationContext` from method `setNumberRangeTranslation` of `SwagMigrationAssistant\Profile\Shopware\Converter\NumberRangeConverter`
- MIG-945 - [BREAKING] Removed parameters `context` and `converted` from method `getLineItems` of `SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter`
- MIG-962 - [BREAKING] Removed getters and setters for `premapping` on `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity`, use them on `\SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity` instead
- MIG-991 - [BREAKING] Added `SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface` parameter to `\SwagMigrationAssistant\Migration\Run\RunService` constructor
- MIG-991 - [BREAKING] Added `$context` parameter to `\SwagMigrationAssistant\Migration\Run\RunServiceInterface::cleanupMigrationData` and implementors
- MIG-991 - [BREAKING] Added `$context` parameter to `\SwagMigrationAssistant\Controller\StatusController::cleanupMigrationData`
- MIG-991 - [BREAKING] Removed property `$status` of `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity` and renamed its field in the corresponding definition, use `$step` instead
- MIG-991 - [BREAKING] Removed method `getStatus` of `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity`, use `getStep` or `getStepValue` instead
- MIG-991 - [BREAKING] Removed method `setStatus` of `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity`, use `SwagMigrationAssistant\Migration\Run\RunTransitionService::transitionToRunStep` instead
- MIG-991 - [BREAKING] Removed constants `STATUS_RUNNING`, `STATUS_FINISHED` and `STATUS_ABORTED` of `SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity`, use `SwagMigrationAssistant\Migration\Run\MigrationStep` instead
- MIG-962 - [BREAKING] Every admin component is now private / internal
- MIG-994 - [BREAKING] Remove fields `user_id` and `access_token` from `swag_migration_run` and corresponding EntityDefinition and related classes
- MIG-1009 - Prevents migrated orders from triggering the adjustment of the product stock
- MIG-1011 - Ensure database attributes (like stringify fetches) are always set on local gateway connections

# 12.0.0
- MIG-983 - Fix migration of shop bounded customers
- MIG-983 - [BREAKING] Changed method name `getAddresses` to `applyAddresses` in `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter`
- MIG-983 - [BREAKING] Changed method name `getCountryTranslation` to `applyCountryTranslation` in `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter`
- MIG-983 - [BREAKING] Changed method name `getCountryStateTranslation` to `applyCountryStateTranslation` in `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter`
- MIG-986 - Fix migration of order tracking codes
- MIG-989 - Improve migration of Media files
- MIG-989 - [BREAKING] Added method `filterUnwrittenData` to `\SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface`
- MIG-990 - Don't let exceptions abort handling of ProcessMediaMessage
- MIG-990 - [BREAKING] Changed constructor of `BaseMediaService`, added `EntityRepository $mediaFileRepository` as new parameter, updated all extending classes.
- MIG-990 - [BREAKING] Added method `setProcessedFlag` in `BaseMediaService` and updated all media processors to use this method
- MIG-990 - [BREAKING] Moved `BaseMediaService` to `SwagMigrationAssistant\Migration\Media\Processor` namespace
- MIG-992 - Fix migration of product seo data
- MIG-1006 - Fix migration of product covers

# 11.0.1
- MIG-988 - A bug in the premapping UI has been fixed to remain compatible with the latest platform version

# 11.0.0
- MIG-951 - Improve the language migration from Shopware 6 to Shopware 6 so the default language is no longer overwritten
- MIG-951 - [BREAKING] Changes the access modifier of the function `\SwagMigrationAssistant\Profile\Shopware6\Converter\LanguageConverter::convertData` from public to protected
- MIG-943 - Fix migration of calculated shipping costs from Shopware 5
- MIG-943 - [BREAKING] Rename method in class `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter` from `getTransactions` to `applyTransactions`
- MIG-943 - [BREAKING] Rename method in class `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter` from `getCountryTranslation` to `applyCountryTranslation`
- MIG-943 - [BREAKING] Rename method in class `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter` from `getCountryStateTranslation` to `applyCountryStateTranslation`
- MIG-943 - [BREAKING] Changed thrown exception from `AssociationEntityRequiredMissingException` to `SwagMigrationAssistant\Exception\MigrationException::associationMissing` in method `\SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter::convert`
- MIG-967, MIG-866 - Improving the migration of order documents 

# 10.0.1
- MIG-971 - Fix compatibility with Shopware 6.6.0.x

# 10.0.0
- NEXT-34526 - [BREAKING] Added native type to `result` parameter of `SwagMigrationAssistant\DataProvider\Provider\Data\AbstractProvider::cleanupSearchResult` method.
- NEXT-34526 - [BREAKING] Removed `DataProvider/Exception/ProviderHasNoTableAccessException.php`, use `SwagMigrationAssistant\Exception\MigrationException::providerHasNoTableAccess` instead.
- NEXT-34526 - [BREAKING] Removed `Profile/Shopware/Exception/ParentEntityForChildNotFoundException.php`, use `SwagMigrationAssistant\Exception\MigrationException::parentEntityForChildNotFound` instead.
- NEXT-34526 - [BREAKING] Changed method name `getMediaTranslation` to `addMediaTranslation` in `SwagMigrationAssistant\Profile\Shopware\Converter\CategoryConverter`.
- NEXT-34526 - [BREAKING] Changed method name `getAttribute` to `addAttribute` in `\SwagMigrationAssistant\Profile\Shopware\Converter\TranslationConverter`.
- NEXT-34526 - [BREAKING] Changed parameter name `blacklist` to `excludeList` of method `SwagMigrationAssistant\Profile\Shopware\Converter\ShopwareConverter::getAttributes`.
- MIG-868 - Improvement of the product migration, the gross purchase price is now calculated
- MIG-966 - [BREAKING] Moved the source files into the `src` directory and renamed the `Test` directory to `tests` without breaking namespaces, but you may need to reactivate or reinstall the plugin. This fixed a bug in our deployment process to properly build the plugin.
- MIG-930 - Improved cross-selling migration, cross-selling no longer overwrites existing cross-selling if there are no changes.

# 9.0.0
- MIG-848 - Improving the assigning of products to sales channel via sub- and language shops
- MIG-920 - Changed supported Shopware 6 version to 6.6 and made adjustments to the profiles
- MIG-920 - Added `Migration/Gateway/HttpClientInterface` to unify the usage of HTTP clients
- MIG-920 - Added `Migration/Gateway/HttpSimpleClient.php` as a simple wrapper around the previously used `GuzzleHttp/Client`
- MIG-920 - [BREAKING] Removed `Profile/Shopware6/Gateway/Connection/AuthClientInterface.php`, use `Migration/Gateway/HttpClientInterface` instead
- MIG-920 - [BREAKING] Changed `Profile/Shopware/Gateway/Api/Reader/EnvironmentReader.php` to use `Migration/Gateway/HttpClientInterface` instead of `GuzzleHttp/Client`
- MIG-920 - [BREAKING] Changed `Profile/Shopware/Gateway/Connection/ConnectionFactory.php` to use `Migration/Gateway/HttpClientInterface` instead of `GuzzleHttp/Client`
- MIG-920 - [BREAKING] Changed `Profile/Shopware/Gateway/Connection/ConnectionFactoryInterface.php` to use `Migration/Gateway/HttpClientInterface` instead of `GuzzleHttp/Client`
- MIG-920 - [BREAKING] Changed `Profile/Shopware6/Gateway/Api/Reader/EnvironmentReader.php` to use `Migration/Gateway/HttpClientInterface` instead of `GuzzleHttp/Client`
- MIG-920 - [BREAKING] Changed `Profile/Shopware6/Gateway/Connection/AuthClient.php` to use `Migration/Gateway/HttpClientInterface` instead of `GuzzleHttp/Client`
- MIG-920 - [BREAKING] Changed `Profile/Shopware6/Gateway/Connection/ConnectionFactory.php` to use `Migration/Gateway/HttpClientInterface` instead of `GuzzleHttp/Client`
- MIG-920 - [BREAKING] Changed `Profile/Shopware6/Gateway/Connection/ConnectionFactoryInterface.php` to use `Migration/Gateway/HttpClientInterface` instead of `GuzzleHttp/Client`
- MIG-920 - Added `Migration/Media/Processor/HttpDownloadServiceBase.php` to unify the download of media files
- MIG-920 - [BREAKING] Changed parent class and implementation of `Profile/Shopware/Media/HttpMediaDownloadService.php` to `Migration/Media/Processor/HttpDownloadServiceBase.php`
- MIG-920 - [BREAKING] Changed parent class and implementation of `Profile/Shopware/Media/HttpOrderDocumentProcessor.php` to `Migration/Media/Processor/HttpDownloadServiceBase.php`
- MIG-920 - [BREAKING] Renamed `Profile/Shopware/Media/HttpOrderDocumentProcessor.php` to `Profile/Shopware/Media/HttpOrderDocumentDownloadService.php`
- MIG-920 - [BREAKING] Changed parent class and implementation of `Profile/Shopware/Media/HttpProductDownloadProcessor.php` to `Migration/Media/Processor/HttpDownloadServiceBase.php`
- MIG-920 - [BREAKING] Renamed `Profile/Shopware/Media/HttpProductDownloadProcessor.php` to `Profile/Shopware/Media/HttpEsdFileDownloadService.php`
- MIG-920 - [BREAKING] Changed parent class and implementation of `Profile/Shopware6/Media/HttpMediaDownloadService.php` to `Migration/Media/Processor/HttpDownloadServiceBase.php`
- MIG-920 - [BREAKING] Changed parent class and implementation of `Profile/Shopware6/Media/HttpOrderDocumentService.php` to `Migration/Media/Processor/HttpDownloadServiceBase.php`
- MIG-920 - [BREAKING] Renamed `Profile/Shopware6/Media/HttpOrderDocumentService.php` to `Profile/Shopware/Media/HttpOrderDocumentDownloadService.php`
- MIG-934 - Added new route `/api/_action/data-provider/download-private-file/{file}` for retrieving digital product files
- MIG-934 - Changed `DataProvider/Provider/Data/ProductProvider.php` to include download media of digital products
- MIG-934 - Changed `Profile/Shopware6/Converter/ProductConverter.php` to include download media of digital products
- MIG-934 - Added `Profile/Shopware6/DataSelection/DataSet/ProductDownloadDataSet.php`
- MIG-934 - Added `Profile/Shopware6/Media/HttpProductDownloadService.php`
- MIG-934 - [BREAKING] Changed `Controller/DataProviderController.php` to final

# 8.0.0
- MIG-274 - Fix migration of cross selling
- MIG-825 - Improve performance of the migration of orders
- MIG-825 - Added option `step-size` to the cli command `migration:migrate` of `Command/MigrationCommand.php`
- MIG-825 - [BREAKING] Added parameter `where` to `fetchIdentifiers` of `Profile/Shopware/Gateway/Local/Reader/AbstractReader.php`
- MIG-825 - [BREAKING] Changed functions of `Profile/Shopware/Gateway/Local/Reader/AbstractReader.php` to be final:
    - `setConnection`
    - `addTableSelection`
    - `buildArrayFromChunks`
    - `cleanupResultSet`
    - `fetchIdentifiers`
    - `getDefaultShopLocale`
    - `mapData`
    - `getDataSetEntity`
- MIG-838 - Add the meta information fields to the migration of category translations
- MIG-839 - Add custom fields to the migration of category translations
- MIG-899 - Changed behavior of the migration of seo urls. It now considers the URL case setting of shopware 5
- MIG-931 - [BREAKING] Changed `Migration/MessageQueue/Handler/ProcessMediaHandler.php` to final
- MIG-931 - [BREAKING] Added `AsyncMessageInterface` to `Migration/MessageQueue/Message/CleanupMigrationMessage.php`
- MIG-931 - [BREAKING] Added `AsyncMessageInterface` to `Migration/MessageQueue/Message/ProcessMediaMessage.php`
- MIG-931 - [BREAKING] Removed methods in `Migration/MessageQueue/Message/ProcessMediaMessage.php`:
    - `readContext`
    - `withContext`
    - `getDataSet`
    - `setDataSet`
- MIG-931 - [BREAKING] Changed return parameter of `getContext` from `string` to `Shopware\Core\Framework\Context` in `Migration/MessageQueue/Message/ProcessMediaMessage.php`
- MIG-931 - [BREAKING] Changed parameter of `setContext` from `string` to `Shopware\Core\Framework\Context` in `Migration/MessageQueue/Message/ProcessMediaMessage.php`
- MIG-931 - Added method `getEntityName` and `setEntityName` to `Migration/MessageQueue/Message/ProcessMediaMessage.php`
- MIG-937 - Always show current shop version used as compatible 6.x instance instead of older instances
- MIG-938 - Fixes wrong calculation when migrating shipping costs

# 7.0.2
- MIG-908 - Fix Shopware 6 migration of `system_config` entities which should not be migrated between different shops

# 7.0.1
- MIG-907 - Fix Shopware 6 profile name in connections

# 7.0.0
- NEXT-31367 - Improve the ConnectionFactory to work more stable
- MIG-881 - Fix bug for converting shipping methods and shipping costs and also migrate shipping methods with a unknown calculation type.
- MIG-878 - Fix migration from SW6.5 to SW6.5. Only same major migrations are supported.
- MIG-905 - Hotfix / known issue for SW6->SW6: `canonicalProductId` of `product` isn't migrated but doesn't prevent migration of products for now.
- MIG-905 - Hotfix / known issue for SW6->SW6: `cmsPageId` of `product` isn't migrated but doesn't prevent migration of products for now.
- MIG-905 - Hotfix / known issue for SW6->SW6: `promotionId` of line items of an order aren't migrated but doesn't prevent migration of orders for now.
- MIG-881 - [BREAKING] Removed method `getDefaultAvailabilityRule` of `Migration/Mapping/MappingServiceInterface.php` and all implementors. Use the premapping of `default_shipping_availability_rule` instead.
- MIG-881 - [BREAKING] Removed parameter `customerRepository` of `Migration/MessageQueue/OrderCountIndexer.php`.
- MIG-878 - [BREAKING] Removed all classes under `Profile/Shopware63`. Use classes under `Profile/Shopware6` instead.
- MIG-878 - [BREAKING] Changed all converters under `Profile/Shopware6/Converter` to be not `abstract` and implement the corresponding `supports` methods. These now replace the old converters under `Profile/Shopware63/Converter`.
- MIG-878 - [BREAKING] Renamed `Profile/Shopware63/Shopware63Profile.php` to `Profile/Shopware6/Shopware6MajorProfile`.
- MIG-878 - [BREAKING] Changed `Profile/Shopware6/Shopware6MajorProfile` to support only the current SW6 major version.
- MIG-878 - [BREAKING] Changed `Profile/Shopware6/Shopware6MajorProfile` to now return `shopware6major` on `getName`.
- MIG-878 - [BREAKING] Renamed `swag-migration-profile-shopware6-api-credential-form` vue component to `swag-migration-profile-shopware6major-api-credential-form`.
- MIG-878 - [BREAKING] Renamed `swag-migration-profile-shopware6-api-page-information` vue component to `swag-migration-profile-shopware6major-api-page-information`.
- MIG-878 - [BREAKING] Removed `swag-migration-profile-shopware63-api-credential-form` vue component.
- MIG-878 - [BREAKING] Removed `swag-migration-profile-shopware63-api-page-information` vue component.
- MIG-878 - [BREAKING] Removed `Profile/Shopware6/DataSelection/DataSet/ProductMainVariantRelationDataSet.php` because it is already migrated with the `product` entity in SW6.
- MIG-878 - [BREAKING] Removed `DataProvider/Provider/Data/ProductMainVariantRelationProvider.php` because it is already migrated with the `product` entity in SW6.
- MIG-878 - [BREAKING] Removed `Profile/Shopware6/Gateway/Api/Reader/ProductMainVariantRelationReader.php` because it is already migrated with the `product` entity in SW6.
- MIG-878 - [BREAKING] Removed `Profile/Shopware6/Converter/ProductMainVariantRelationConverter.php` because it is already migrated with the `product` entity in SW6.

# 6.0.1
- MIG-887 - Improve performance of the endpoint, which captures all data to be written afterwards

# 6.0.0
- MIG-879 - Fix tax free order migration from SW5
- MIG-859 - [BREAKING] Removed method `pushMapping` of `Migration/Mapping/MappingServiceInterface.php` and all implementors. Use `getOrCreateMapping` instead.
- MIG-859 - [BREAKING] Removed method `pushValueMapping` of `Migration/Mapping/MappingServiceInterface.php` and all implementors. Use `getOrCreateMapping` instead.
- MIG-859 - [BREAKING] Removed method `bulkDeleteMapping` of `Migration/Mapping/MappingServiceInterface.php` and all implementors.
- MIG-859 - [BREAKING] Added default parameter `$entityValue` to `getOrCreateMapping` of `Migration/Mapping/MappingServiceInterface.php` and all implementors. Update implementors.
- MIG-859 - [BREAKING] Added default parameter `$entityValue` to `createMapping` of `Migration/Mapping/MappingServiceInterface.php` and all implementors. Update implementors.

# 5.1.2
- MIG-871 - Fix bug for converting tax free orders
- MIG-869 - Add additional information to SW6 profile page

# 5.1.1
- MIG-870 - Fix bug during migration of products

# 5.1.0
- NEXT-22545 - Add migration of digital products

# 5.0.0
- MIG-847 - Shopware 6.5 compatibility
- MIG-827 - Fix migration of shipping methods with time configuration
- MIG-829 - Fixed the progress bar is displayed incorrectly at a specific viewport
- NTR - Migration Custom Product processing

# 4.2.5
- MIG-293 - Improve performance of order documents migration

# 4.2.4
- MIG-246 - Fix resetting of connection settings
- MIG-262 - Customer migrations are no longer grouped by email address
- MIG-279 - Fix pre-mapping

# 4.2.3
- MIG-269 - Fix missing order data for systems where MySQL trigger do not work

# 4.2.2
- MIG-263 - Fixes a problem, where order addresses might be erroneously identical or interchanged
- MIG-260 - Fix sales channel migration with Shopware language pack

# 4.2.1
- MIG-252 - Fix profile installer view

# 4.2.0
- MIG-100 - Add migration profile for Shopware 5.7
- MIG-243 - Fix migration of orders for Shopware 6 profile
- MIG-247 - Fix write protection errors for Shopware 6 migration

# 4.1.1
- MIG-206 - Migrate product creation date
- MIG-237 - Fix customer vatId migration
- MIG-240 - Optimize customer order count indexer

# 4.1.0
- MIG-126 - Add migration profile for Shopware 6
- MIG-221 - Fix migration of invoices
- MIG-224 - Optimize attribute value migration handling from SW5
- MIG-233 - Migration of notes / wishlists added

# 4.0.0
- MIG-203 - Shopware 6.4 compatibility
- MIG-220 - Optimize product image cover migration

# 3.0.2
- MIG-218 - Prevents abortion of a migration if customer has an invalid email address
- MIG-219 - Solves an issue on migration translations of custom fields

# 3.0.1
- MIG-213 - Prevents abortion of a migration if some products with variants could not be written
- MIG-214 - Improves progress display in CLI
- MIG-216 - Fix issue with customer emails longer than 64 characters

# 3.0.0
- MIG-125 - Improves the migration of orders, that customer order count is indexed
- MIG-181 - Provide migration of main variant information for Shopware 5.4 / 5.6
- MIG-182 - Migration of vouchers added
- MIG-187 - Improves the migration of media without filename
- MIG-188 - Improves media download stability
- MIG-189 - Fix migration of product line items
- MIG-194 - Optimizes sales channel migration
- MIG-196 - Improve the extendability of the plugin

# 2.2.2
- MIG-110 - Improves the migration of media
- MIG-114 - Provide migration of main variant information
- MIG-118 - Fix migration of credit line items
- MIG-120 - Solves an issue when loading the pre-mapping
- MIG-162 - Solves an issue on migrating products with empty custom fields
- MIG-167 - Solves an issue on migrating custom field values
- MIG-168 - Optimized request options

# 2.2.1
- MIG-105 - Add warning if default languages differ
- MIG-107 - Improves the migration of shipping methods
- MIG-109 - Improve migration of orders

# 2.2.0
- MIG-75 - Improves the takeover of a migration
- MIG-106 - Improves the migration of order line items
- MIG-124 - Added ACL privileges

# 2.1.2
- MIG-85 - Migrate customer comments in orders
- MIG-90 - Fix variant migration
- MIG-92 - Fix broken history download
- MIG-98 - Fix premappings without description
- MIG-103 - Improves the migration of variant translations

# 2.1.1
- MIG-39 - Optimized basic converter
- MIG-72 - Recognize correct category type when external link is set
- MIG-73 - Recognize variant attribute translations on SW5 migration
- MIG-74 - Optimize migration of custom fields

# 2.1.0
- MIG-13 - Migrate product reviews without customer
- MIG-28 - Optimized rebuilding of container for deactivation and activation of the plugin

# 2.0.0
- MIG-3 - Fixes a problem with migrating order documents
- MIG-5 - Improve snippet loading of DataSets
- MIG-14 - Implement deletion of logs from a selected run
- MIG-22 - Fixes a problem with migrating orders, caused by aborted orders
- MIG-23 - Fixes a problem with log file download

# 1.7.1
- MIG-6 - Add functionality to save premapping without start migration for CLI support

# 1.7.0
- PT-11910 - Add migration of cross selling
- PT-11922 - Shopware 6.3 compatibility
- PT-11955 - Fixes a problem with saving media

# 1.6.0
- PT-11692 - Add functionality to finish migration and remove unneeded data
- PT-11864 - Improve media file processing
- PT-11942 - Improve migration of product-translations

# 1.5.3
- PT-11845 - Improve migration of customers
- PT-11855 - Improve migration of media

# 1.5.2
- PT-11788 - Introduce migration of pseudo prices from SW5

# 1.5.1
- PT-11819 - Optimize product variant migration for Shopware 5 profile

# 1.5.0
- PT-11692 - Move migration dashboard card to own component
- PT-11747 - Ignore seo urls without type id
- PT-11764 - Add sorting of pre-mapping values

# 1.4.2
- PT-11689 - Add survey to get feedback about the tool from users

# 1.4.1
- NTR - Solve issue with cache service injection

# 1.4.0
- PT-11497 - Solves an issue with incorrect connection state
- PT-11601 - Shopware 6.2 compatibility
- PT-11462 - Solves an issue regarding order migration

# 1.3.0
- PT-11586 - Optimized product migration from Shopware 5
- PT-11617 - Fix error with too many open database connections

# 1.2.2
- NTR - Fixes an issue with the layout when resetting the checksums

# 1.2.1
- NTR - Fixes an issue migrating media folder thumbnail settings from Shopware 5

# 1.2.0
- PT-11450 - It is now possible to reset the checksums via a button in the connection management drop-down menu.
- PT-11525 - Optimize media migration process

# 1.1.0
- PT-10832 - Preventing an undesired state when creating new connections
- PT-10983 - Technical concept of the user interfaces changed to Vuex
- PT-11331 - Fix request timeout for bigger migrations
- PT-11394 - Fix product visibility for nested shop structures
- PT-11400 - Migration error at wrong defined thumbnail sizes fixed

# 1.0.3
- PT-11329 - Migrate meta data for products and categories
- NTR - Solves an issue with database field length difference between SW5 and SW6

# 1.0.2
- NTR - Improve progress calculation for big amounts of data

# 1.0.1
- NTR - Solves an issue with delta checksums when aborting migration

# 1.0.0
- PT-11113 - Refactor plugin icons
- PT-11111 - Refactor profile icon for external profile
- NTR - Fix refresh after wizard install of external profile
- NTR - Snippet renaming
- PT-11252 - Force number range migration

# 0.40.0
- PT-11014- Add magento onboarding to migration wizard
- PT-11016 - Refactor first migration wizard page
- PT-11017 - Add migration card to dashboard
- PT-11033 - Fix migration of categories
- PT-11020 - Implement measurement call
- NTR - Fix new plugin folder structure
- NTR - Stabilize migration data writer
- NTR - Refactor datasets
- NTR - Refactor api total fetching
- NTR - Refactor reader interface and classes
- NTR - Fix product image cover when only 1 image exists

# 0.30.1
- PT-10925 - Call indexing controller after every migration
- PT-10948 - Prevent duplicate document types
- PT-10946 - Migrate customer language


# 0.30.0
- PT-10629 - Raise test coverage
- PT-10761 - Implement new frontend data handling
- PT-10783 - Migrate empty labels with attribute name
- PT-10797 - Fix product active state during migration
- NTR - Implement partial indexing via message queue and fix tests
- PT-10800 - Fix rising of mapping entries for properties and options
- PT-10818 - Fix custom field migration
- PT-10819 - Fix newsletter recipients migration
- PT-10835 - Fix migration of shippingfree products
- PT-10844 - Migrate deliveryTime of products
- PT-10769 - Fix json_encode error on logging
- PT-10846 - Migrate product reviews
- PT-10847 - Fix sales channel error for multiple migrations
- NTR - Fix order state premapping
- PT-10793 - Use checksum for migration
- PT-10861 - Migrate seo urls
- PT-10718 - Remove unprocessed media entries
- PT-10875 - Cleanup unwritten migration data when run new starts

# 0.20.0
- Refactor imports to global object
- Refactor deprecated data handling imports

# 0.10.1
- Add default theme to sales channels
- Fix indexing after migration

# 0.10.0
- Implement Shopware 5.4 & Shopware 5.6 profiles
- Refactor converter and reader structure

# 0.9.0
- First version of the Shopware Migration Assistant for Shopware 6
