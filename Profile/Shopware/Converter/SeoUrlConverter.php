<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedSeoUrlType;

abstract class SeoUrlConverter extends ShopwareConverter
{
    protected const TYPE_CATEGORY = 'cat';
    protected const TYPE_PRODUCT = 'detail';

    protected const ROUTE_NAME_NAVIGATION = 'frontend.navigation.page';
    protected const ROUTE_NAME_PRODUCT = 'frontend.detail.page';

    /**
     * @var string
     */
    private $connectionId;

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->connectionId = $migrationContext->getConnection()->getId();
        $originalData = $data;

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SEO_URL,
            $data['id'],
            $context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityUuid'];
        unset($data['id']);

        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            $data['subshopID'],
            $context
        );

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::SALES_CHANNEL,
                    $data['subshopID'],
                    DefaultEntities::SEO_URL
                )
            );

            return new ConvertStruct(null, $originalData);
        }
        $converted['salesChannelId'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        unset($data['subshopID']);

        $converted['languageId'] = $this->mappingService->getLanguageUuid(
            $this->connectionId,
            $data['_locale'],
            $context
        );
        if ($converted['languageId'] === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::LANGUAGE,
                    $data['_locale'],
                    DefaultEntities::SEO_URL
                )
            );

            return new ConvertStruct(null, $originalData);
        }
        $this->mappingIds[] = $converted['languageId'];
        unset($data['_locale']);

        if ($data['type'] === self::TYPE_PRODUCT) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT . '_mainProduct',
                $data['typeId'],
                $context
            );

            if ($mapping === null) {
                $this->loggingService->addLogEntry(
                    new AssociationRequiredMissingLog(
                        $migrationContext->getRunUuid(),
                        DefaultEntities::PRODUCT,
                        $data['typeId'],
                        DefaultEntities::SEO_URL
                    )
                );

                return new ConvertStruct(null, $originalData);
            }
            $converted['foreignKey'] = $mapping['entityUuid'];
            $converted['routeName'] = self::ROUTE_NAME_PRODUCT;
            $converted['pathInfo'] = '/detail/' . $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];
        } elseif ($data['type'] === self::TYPE_CATEGORY) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::CATEGORY,
                $data['typeId'],
                $context
            );

            if ($mapping === null) {
                $this->loggingService->addLogEntry(
                    new AssociationRequiredMissingLog(
                        $migrationContext->getRunUuid(),
                        DefaultEntities::CATEGORY,
                        $data['typeId'],
                        DefaultEntities::SEO_URL
                    )
                );

                return new ConvertStruct(null, $originalData);
            }
            $converted['foreignKey'] = $mapping['entityUuid'];
            $converted['routeName'] = self::ROUTE_NAME_NAVIGATION;
            $converted['pathInfo'] = '/navigation/' . $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];
        } else {
            $this->loggingService->addLogEntry(
                new UnsupportedSeoUrlType(
                    $migrationContext->getRunUuid(),
                    $data['type'],
                    DefaultEntities::SEO_URL,
                    $originalData['id']
                )
            );

            return new ConvertStruct(null, $originalData);
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

        return new ConvertStruct($converted, $data, $this->mainMapping['id']);
    }
}
