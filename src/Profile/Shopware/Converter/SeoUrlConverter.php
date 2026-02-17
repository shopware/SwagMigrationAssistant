<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Content\Seo\SeoUrl\SeoUrlDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertAssociationMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertObjectTypeUnsupportedLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('fundamentals@after-sales')]
abstract class SeoUrlConverter extends ShopwareConverter
{
    protected const TYPE_CATEGORY = 'cat';
    protected const TYPE_PRODUCT = 'detail';

    protected const ROUTE_NAME_NAVIGATION = 'frontend.navigation.page';
    protected const ROUTE_NAME_PRODUCT = 'frontend.detail.page';

    private string $connectionId;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected readonly LanguageLookup $languageLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $originalData = $data;

        $connection = $migrationContext->getConnection();
        $this->connectionId = $connection->getId();

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SEO_URL,
            $data['id'],
            $context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityId'];
        unset($data['id']);

        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            $data['subshopID'],
            $context
        );

        if ($mapping === null) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(SeoUrlDefinition::ENTITY_NAME)
                    ->withFieldName('salesChannelId')
                    ->withFieldSourcePath('subshopID')
                    ->withSourceData($data)
                    ->withConvertedData($converted)
                    ->build(ConvertAssociationMissingLog::class)
            );

            return new ConvertStruct(null, $originalData);
        }

        $converted['salesChannelId'] = $mapping['entityId'];
        $this->mappingIds[] = $mapping['id'];
        unset($data['subshopID']);

        $converted['languageId'] = $this->languageLookup->get($data['_locale'], $context);
        if ($converted['languageId'] !== null) {
            $this->mappingIds[] = $converted['languageId'];
            unset($data['_locale']);
        }

        if ($data['type'] === self::TYPE_PRODUCT && isset($data['typeId'])) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_MAIN,
                $data['typeId'],
                $context
            );

            if ($mapping === null) {
                $mapping = $this->mappingService->getMapping(
                    $this->connectionId,
                    DefaultEntities::PRODUCT_CONTAINER,
                    $data['typeId'],
                    $context
                );
            }

            if ($mapping === null) {
                $this->loggingService->log(
                    MigrationLogBuilder::fromMigrationContext($migrationContext)
                        ->withEntityName(SeoUrlDefinition::ENTITY_NAME)
                        ->withFieldName('foreignKey')
                        ->withFieldSourcePath('type')
                        ->withSourceData($data)
                        ->build(ConvertAssociationMissingLog::class)
                );

                return new ConvertStruct(null, $originalData);
            }

            $converted['foreignKey'] = $mapping['entityId'];
            $converted['routeName'] = self::ROUTE_NAME_PRODUCT;
            $converted['pathInfo'] = '/detail/' . $mapping['entityId'];

            $this->mappingIds[] = $mapping['id'];
        } elseif ($data['type'] === self::TYPE_CATEGORY && isset($data['typeId'])) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::CATEGORY,
                $data['typeId'],
                $context
            );

            if ($mapping === null) {
                $this->loggingService->log(
                    MigrationLogBuilder::fromMigrationContext($migrationContext)
                        ->withEntityName(SeoUrlDefinition::ENTITY_NAME)
                        ->withFieldName('foreignKey')
                        ->withFieldSourcePath('type')
                        ->withSourceData($data)
                        ->build(ConvertAssociationMissingLog::class)
                );

                return new ConvertStruct(null, $originalData);
            }

            $converted['foreignKey'] = $mapping['entityId'];
            $converted['routeName'] = self::ROUTE_NAME_NAVIGATION;
            $converted['pathInfo'] = '/navigation/' . $mapping['entityId'];

            $this->mappingIds[] = $mapping['id'];
        } else {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(DefaultEntities::SEO_URL)
                    ->withSourceData($data)
                    ->withFieldName('type')
                    ->build(ConvertObjectTypeUnsupportedLog::class)
            );

            // skip this entity because we can't migrate this seo type from SW5
            return new ConvertStruct(null, $data, $this->mainMapping['id'] ?? null);
        }
        unset($data['type'], $data['typeId']);

        $this->convertValue($converted, 'seoPathInfo', $data, 'path');
        $converted['isModified'] = false;
        if ($data['main'] === '1') {
            $converted['isCanonical'] = true;
            $converted['isModified'] = true;
        }
        unset($data['org_path'], $data['main']);

        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $data, $this->mainMapping['id'] ?? null);
    }
}
