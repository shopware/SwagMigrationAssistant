<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('fundamentals@after-sales')]
abstract class ProductOptionRelationConverter extends ShopwareConverter
{
    protected string $connectionId;

    /**
     * @var array<mixed>
     */
    protected array $originalData;

    public function getSourceIdentifier(array $data): string
    {
        return $data['identifier'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->originalData = $data;

        $connection = $migrationContext->getConnection();
        $this->connectionId = $connection->getId();

        $productContainerMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_CONTAINER,
            $data['productId'],
            $context
        );

        $relationMapping = null;
        if ($productContainerMapping !== null) {
            $this->mappingIds[] = $productContainerMapping['id'];
            $relationMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_PROPERTY,
                $data['id'] . '_' . $productContainerMapping['entityId'],
                $context
            );
        }

        // use "old" relation mapping if exists < v.1.3
        if ($relationMapping !== null) {
            $this->mainMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_OPTION_RELATION,
                $data['identifier'],
                $context,
                null,
                null,
                $relationMapping['entityId']
            );
        } else {
            $this->mainMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_OPTION_RELATION,
                $data['identifier'],
                $context
            );
        }

        $optionMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            Hasher::hash(\mb_strtolower($data['name'] . '_' . $data['group']['name']), 'md5'),
            $context
        );

        $converted = [];

        if ($optionMapping !== null) {
            $this->mappingIds[] = $optionMapping['id'];
            $converted['configuratorSettings'][] = [
                'id' => $this->mainMapping['entityId'],
                'optionId' => $optionMapping['entityId'],
            ];
        }

        if (isset($productContainerMapping['entityId'])) {
            $converted['id'] = $productContainerMapping['entityId'];
        }

        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
