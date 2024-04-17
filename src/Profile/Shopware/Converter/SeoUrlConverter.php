<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedSeoUrlType;

#[Package('services-settings')]
abstract class SeoUrlConverter extends ShopwareConverter
{
    protected const TYPE_CATEGORY = 'cat';
    protected const TYPE_PRODUCT = 'detail';

    protected const ROUTE_NAME_NAVIGATION = 'frontend.navigation.page';
    protected const ROUTE_NAME_PRODUCT = 'frontend.detail.page';

    private string $connectionId;

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $originalData = $data;

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

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
            }

            $converted['foreignKey'] = $mapping['entityUuid'];
            $converted['routeName'] = self::ROUTE_NAME_PRODUCT;
            $converted['pathInfo'] = '/detail/' . $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];
        } elseif ($data['type'] === self::TYPE_CATEGORY && isset($data['typeId'])) {
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

        return new ConvertStruct($converted, $data, $this->mainMapping['id'] ?? null);
    }
}
