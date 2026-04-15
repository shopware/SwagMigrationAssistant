<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\DataAbstractionLayer\CustomerIndexer;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Product\Stock\StockStorage;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Store\Services\TrackingEventClient;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Controller\ErrorResolutionController;
use SwagMigrationAssistant\Controller\HistoryController;
use SwagMigrationAssistant\Controller\PremappingController;
use SwagMigrationAssistant\Controller\StatusController;
use SwagMigrationAssistant\Core\Content\Product\Stock\StockStorageDecorator;
use SwagMigrationAssistant\Migration\Connection\Fingerprint\MigrationFingerprintService;
use SwagMigrationAssistant\Migration\Connection\MigrationConnectionFactory;
use SwagMigrationAssistant\Migration\Converter\Converter;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistry;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\ErrorResolution\MigrationErrorResolutionService;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\History\HistoryService;
use SwagMigrationAssistant\Migration\History\LogGroupingService;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CmsPageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryStateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CurrencyLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DefaultCmsPageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DeliveryTimeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DocumentTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\GlobalDocumentBaseConfigLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LocaleLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LowestRootCategoryLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MailTemplateTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaDefaultFolderLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaThumbnailSizeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\NumberRangeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\NumberRangeTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\ProductSortingLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SalesChannelLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SalesChannelTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SalutationLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SeoUrlTemplateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\StateMachineStateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemConfigLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemDefaultMailTemplateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxRuleLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxRuleTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistry;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\Media\Processor\HttpDownloadServiceBase;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileDefinition;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\MigrationProcessHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\MigrationProcessorRegistry;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\AbortingProcessor;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\AbstractProcessor;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\CleanUpProcessor;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\FetchingProcessor;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\IndexingProcessor;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\MediaProcessingProcessor;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\WritingProcessor;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ResetChecksumHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ThemeAssignHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\TruncateMigrationHandler;
use SwagMigrationAssistant\Migration\MessageQueue\OrderCountIndexer;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\Premapping\PremappingReaderRegistry;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistry;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\RunTransitionService;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverter;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcher;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriter;
use SwagMigrationAssistant\Migration\Service\PremappingService;
use SwagMigrationAssistant\Migration\Validation\MigrationEntityValidationService;
use SwagMigrationAssistant\Migration\Validation\MigrationFieldValidationService;
use SwagMigrationAssistant\Migration\Writer\WriterRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(MigrationConfiguration::class);

    $services->set(LoggingService::class)
        ->args([
            service('swag_migration_logging.repository'),
            service('logger'),
            service(MigrationConfiguration::class),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(MediaFileService::class)
        ->args([
            service('swag_migration_media_file.repository'),
            service(EntityWriter::class),
            service(SwagMigrationMediaFileDefinition::class),
            service(ConverterRegistry::class),
            service('logger'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(Converter::class)
        ->abstract()
        ->args([
            service(MappingService::class),
            service(LoggingService::class),
        ]);

    $services->set(ConverterRegistry::class)
        ->args([tagged_iterator('shopware.migration.converter')]);

    $services->set(MigrationContextFactory::class)
        ->args([
            service(ProfileRegistry::class),
            service(GatewayRegistry::class),
            service(DataSetRegistry::class),
            service('swag_migration_general_setting.repository'),
            service('swag_migration_connection.repository'),
        ]);

    $services->set(MappingService::class)
        ->args([
            service('swag_migration_mapping.repository'),
            service(EntityWriter::class),
            service(SwagMigrationMappingDefinition::class),
            service(Connection::class),
            service('logger'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(CountryLookup::class)
        ->args([service('country.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(CountryStateLookup::class)
        ->args([service('country_state.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(CurrencyLookup::class)
        ->args([service('currency.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(DefaultCmsPageLookup::class)
        ->args([service('cms_page.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(DeliveryTimeLookup::class)
        ->args([service('delivery_time.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(DocumentTypeLookup::class)
        ->args([service('document_type.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(LanguageLookup::class)
        ->args([
            service('language.repository'),
            service(LocaleLookup::class),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(LocaleLookup::class)
        ->args([service('locale.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(LowestRootCategoryLookup::class)
        ->args([service('category.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(MediaDefaultFolderLookup::class)
        ->args([service('media_default_folder.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(MediaThumbnailSizeLookup::class)
        ->args([service('media_thumbnail_size.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(NumberRangeLookup::class)
        ->args([service('number_range.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(SeoUrlTemplateLookup::class)
        ->args([service('seo_url_template.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(TaxLookup::class)
        ->args([service('tax.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(MailTemplateTypeLookup::class)
        ->args([service('mail_template_type.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(SystemDefaultMailTemplateLookup::class)
        ->args([service('mail_template.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(NumberRangeTypeLookup::class)
        ->args([service('number_range_type.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(SalutationLookup::class)
        ->args([service('salutation.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(SalesChannelTypeLookup::class)
        ->args([service('sales_channel_type.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(SalesChannelLookup::class)
        ->args([service('sales_channel.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(StateMachineStateLookup::class)
        ->args([service('state_machine_state.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(SystemConfigLookup::class)
        ->args([service('system_config.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(GlobalDocumentBaseConfigLookup::class)
        ->args([service('document_base_config.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(CmsPageLookup::class)
        ->args([service('cms_page.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(TaxRuleLookup::class)
        ->args([service('tax_rule.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(TaxRuleTypeLookup::class)
        ->args([service('tax_rule_type.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(ProductSortingLookup::class)
        ->args([service('product_sorting.repository')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(RunService::class)
        ->args([
            service('swag_migration_run.repository'),
            service('swag_migration_connection.repository'),
            service(MigrationDataFetcher::class),
            service(DataSelectionRegistry::class),
            service('sales_channel.repository'),
            service('theme.repository'),
            service('swag_migration_general_setting.repository'),
            service(ThemeService::class),
            service(MappingService::class),
            service(Connection::class),
            service(LoggingService::class),
            service(TrackingEventClient::class),
            service('messenger.default_bus'),
            service(MigrationContextFactory::class),
            service(PremappingService::class),
            service(RunTransitionService::class),
            service(LogGroupingService::class),
        ]);

    $services->set(RunTransitionService::class)
        ->args([service(Connection::class)]);

    $services->set(LogGroupingService::class)
        ->args([service(Connection::class)]);

    $services->set(HistoryService::class)
        ->args([
            service('swag_migration_logging.repository'),
            service('swag_migration_run.repository'),
            service(MigrationConfiguration::class),
        ]);

    $services->set(MigrationDataFetcher::class)
        ->public()
        ->args([
            service(GatewayRegistry::class),
            service(LoggingService::class),
        ]);

    $services->set(ReaderRegistry::class)
        ->args([tagged_iterator('shopware.migration.reader')]);

    $services->set(MigrationDataConverter::class)
        ->public()
        ->args([
            service(EntityWriter::class),
            service(ConverterRegistry::class),
            service(MediaFileService::class),
            service(LoggingService::class),
            service(SwagMigrationDataDefinition::class),
            service(MappingService::class),
            service(MigrationEntityValidationService::class),
        ]);

    $services->set(MigrationDataWriter::class)
        ->public()
        ->args([
            service(EntityWriter::class),
            service('swag_migration_data.repository'),
            service(WriterRegistry::class),
            service(MediaFileService::class),
            service(LoggingService::class),
            service(SwagMigrationDataDefinition::class),
            service('swag_migration_mapping.repository'),
            service(MigrationErrorResolutionService::class),
        ]);

    $services->set(MediaFileProcessorRegistry::class)
        ->args([tagged_iterator('shopware.migration.media_file_processor')]);

    $services->set(HttpDownloadServiceBase::class)
        ->abstract()
        ->args([
            service(Connection::class),
            service('swag_migration_media_file.repository'),
            service(FileSaver::class),
            service(LoggingService::class),
            service(MigrationConfiguration::class),
        ]);

    $services->set(PremappingController::class)
        ->public()
        ->args([
            service(PremappingService::class),
            service(MigrationContextFactory::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(MigrationFingerprintService::class)
        ->args([service('swag_migration_connection.repository')]);

    $services->set(MigrationConnectionFactory::class)
        ->args([
            service('swag_migration_connection.repository'),
            service(MigrationContextFactory::class),
            service(MigrationDataFetcher::class),
            service(MigrationFingerprintService::class),
        ]);

    $services->set(StatusController::class)
        ->public()
        ->args([
            service(MigrationDataFetcher::class),
            service(RunService::class),
            service(DataSelectionRegistry::class),
            service('swag_migration_connection.repository'),
            service(ProfileRegistry::class),
            service(GatewayRegistry::class),
            service(MigrationContextFactory::class),
            service('swag_migration_general_setting.repository'),
            service(MigrationConnectionFactory::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(HistoryController::class)
        ->public()
        ->args([
            service(HistoryService::class),
            service(LogGroupingService::class),
            '%shopware.api.max_limit%',
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(ErrorResolutionController::class)
        ->public()
        ->args([service(MigrationFieldValidationService::class)])
        ->call('setContainer', [service('service_container')]);

    $services->set(DataSelectionRegistry::class)
        ->args([tagged_iterator('shopware.migration.data_selection')]);

    $services->set(DataSetRegistry::class)
        ->args([tagged_iterator('shopware.migration.data_set')]);

    $services->set(PremappingReaderRegistry::class)
        ->args([tagged_iterator('shopware.migration.pre_mapping_reader')]);

    $services->set(PremappingService::class)
        ->args([
            service(PremappingReaderRegistry::class),
            service(MappingService::class),
            service('swag_migration_mapping.repository'),
            service('swag_migration_connection.repository'),
        ]);

    $services->set(TruncateMigrationHandler::class)
        ->args([
            service(Connection::class),
            service('messenger.default_bus'),
            service(MigrationConfiguration::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(ThemeAssignHandler::class)
        ->args([service(RunService::class)])
        ->tag('messenger.message_handler');

    $services->set(MigrationProcessHandler::class)
        ->args([
            service('swag_migration_run.repository'),
            service(MigrationContextFactory::class),
            service(MigrationProcessorRegistry::class),
            service(MigrationConfiguration::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(ResetChecksumHandler::class)
        ->args([
            service(Connection::class),
            service('messenger.default_bus'),
            service('swag_migration_run.repository'),
            service(RunTransitionService::class),
            service(MigrationConfiguration::class),
        ])
        ->tag('messenger.message_handler');

    $services->set(OrderCountIndexer::class)
        ->decorate(CustomerIndexer::class)
        ->args([
            service('SwagMigrationAssistant\Migration\MessageQueue\OrderCountIndexer.inner'),
            service(Connection::class),
        ]);

    $services->set(MigrationProcessorRegistry::class)
        ->args([tagged_iterator('shopware.migration.processor')]);

    $services->set(AbstractProcessor::class)
        ->abstract()
        ->args([
            service('swag_migration_run.repository'),
            service('swag_migration_data.repository'),
            service('swag_migration_media_file.repository'),
            service(RunTransitionService::class),
        ]);

    $services->set(FetchingProcessor::class)
        ->parent(AbstractProcessor::class)
        ->args([
            service(MigrationDataFetcher::class),
            service(MigrationDataConverter::class),
            service('messenger.default_bus'),
        ])
        ->tag('shopware.migration.processor');

    $services->set(WritingProcessor::class)
        ->parent(AbstractProcessor::class)
        ->args([
            service(MigrationDataWriter::class),
            service('messenger.default_bus'),
        ])
        ->tag('shopware.migration.processor');

    $services->set(AbortingProcessor::class)
        ->parent(AbstractProcessor::class)
        ->args([service('messenger.default_bus')])
        ->tag('shopware.migration.processor');

    $services->set(CleanUpProcessor::class)
        ->parent(AbstractProcessor::class)
        ->args([
            service(Connection::class),
            service('messenger.default_bus'),
            service(MigrationConfiguration::class),
        ])
        ->tag('shopware.migration.processor');

    $services->set(IndexingProcessor::class)
        ->parent(AbstractProcessor::class)
        ->args([
            service('cache.object'),
            service(EntityIndexerRegistry::class),
            service('messenger.default_bus'),
        ])
        ->tag('shopware.migration.processor');

    $services->set(MediaProcessingProcessor::class)
        ->parent(AbstractProcessor::class)
        ->args([
            service('messenger.default_bus'),
            service(LoggingService::class),
            service(Connection::class),
            service(MediaFileProcessorRegistry::class),
            service(DataSetRegistry::class),
            service(MigrationConfiguration::class),
        ])
        ->tag('shopware.migration.processor');

    $services->set(StockStorageDecorator::class)
        ->decorate(StockStorage::class)
        ->args([service('SwagMigrationAssistant\Core\Content\Product\Stock\StockStorageDecorator.inner')]);

    $services->set(MigrationEntityValidationService::class)
        ->args([
            service(DefinitionInstanceRegistry::class),
            service(EventDispatcherInterface::class),
            service(LoggingService::class),
            service(MigrationFieldValidationService::class),
            service(Connection::class),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(MigrationFieldValidationService::class)
        ->args([service(DefinitionInstanceRegistry::class)])
        ->tag('kernel.reset', ['method' => 'reset']);
};
