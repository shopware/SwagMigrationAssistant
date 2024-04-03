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
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class ProductReviewConverter extends ShopwareConverter
{
    /**
     * @var list<string>
     */
    protected array $requiredDataFieldKeys = [
        '_locale',
        'articleID',
    ];

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $fields = $this->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);

        if (!empty($fields)) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $migrationContext->getRunUuid(),
                DefaultEntities::PRODUCT_REVIEW,
                $data['id'],
                \implode(',', $fields)
            ));

            return new ConvertStruct(null, $data);
        }
        $this->generateChecksum($data);
        $originalData = $data;
        $mainLocale = $data['_locale'];
        unset($data['_locale']);

        $connection = $migrationContext->getConnection();
        $connectionId = '';
        if ($connection !== null) {
            $connectionId = $connection->getId();
        }

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::PRODUCT_REVIEW,
            $data['id'],
            $context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityUuid'];
        unset($data['id']);

        $mapping = $this->mappingService->getMapping(
            $connectionId,
            DefaultEntities::PRODUCT_MAIN,
            $data['articleID'],
            $context
        );

        if ($mapping === null) {
            $mapping = $this->mappingService->getMapping(
                $connectionId,
                DefaultEntities::PRODUCT_CONTAINER,
                $data['articleID'],
                $context
            );

            if ($mapping === null) {
                $this->loggingService->addLogEntry(
                    new AssociationRequiredMissingLog(
                        $migrationContext->getRunUuid(),
                        DefaultEntities::PRODUCT,
                        $data['articleID'],
                        DefaultEntities::PRODUCT_REVIEW
                    )
                );

                return new ConvertStruct(null, $originalData);
            }
        }
        $converted['productId'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        unset($data['articleID']);

        if (isset($data['email'])) {
            $mapping = $this->mappingService->getMapping(
                $connectionId,
                DefaultEntities::CUSTOMER,
                $data['email'],
                $context
            );

            if ($mapping !== null) {
                $converted['customerId'] = $mapping['entityUuid'];
                $this->mappingIds[] = $mapping['id'];
            }
        }
        $this->convertValue($converted, 'externalEmail', $data, 'email');
        $this->convertValue($converted, 'externalUser', $data, 'name');

        $shopId = $data['shop_id'] === null ? $data['mainShopId'] : $data['shop_id'];
        $mapping = $this->mappingService->getMapping(
            $connectionId,
            DefaultEntities::SALES_CHANNEL,
            $shopId,
            $context
        );

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::SALES_CHANNEL,
                    $shopId,
                    DefaultEntities::PRODUCT_REVIEW
                )
            );

            return new ConvertStruct(null, $originalData);
        }
        $converted['salesChannelId'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        unset($data['shop_id'], $data['mainShopId']);

        $converted['languageId'] = $this->mappingService->getLanguageUuid(
            $connectionId,
            $mainLocale,
            $context
        );

        if ($converted['languageId'] === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::LANGUAGE,
                    $mainLocale,
                    DefaultEntities::PRODUCT_REVIEW
                )
            );

            return new ConvertStruct(null, $originalData);
        }

        $this->convertValue($converted, 'title', $data, 'headline');
        if (empty($converted['title'])) {
            $converted['title'] = \mb_substr($data['comment'], 0, 30) . '...';
        }
        $this->convertValue($converted, 'content', $data, 'comment');
        $this->convertValue($converted, 'points', $data, 'points', self::TYPE_FLOAT);
        $this->convertValue($converted, 'status', $data, 'active', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'comment', $data, 'answer');
        $this->convertValue($converted, 'createdAt', $data, 'datum');

        $this->updateMainMapping($migrationContext, $context);

        // There is no equivalent field
        unset(
            $data['answer_date']
        );

        $resultData = $data;
        if (empty($resultData)) {
            $resultData = null;
        }

        return new ConvertStruct($converted, $resultData, $this->mainMapping['id'] ?? null);
    }
}
