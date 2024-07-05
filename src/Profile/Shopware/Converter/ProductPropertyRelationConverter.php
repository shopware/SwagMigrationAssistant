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
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class ProductPropertyRelationConverter extends ShopwareConverter
{
    protected string $connectionId;

    protected Context $context;

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
        $this->context = $context;
        $this->originalData = $data;

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

        $productMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_CONTAINER,
            $data['productId'],
            $context
        );

        if ($productMapping === null) {
            $productMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_MAIN,
                $data['productId'],
                $context
            );

            if ($productMapping === null) {
                return new ConvertStruct(null, $this->originalData);
            }
        }
        $this->mappingIds[] = $productMapping['id'];
        $optionMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            \hash('md5', \mb_strtolower($data['name'] . '_' . $data['group']['name'])),
            $context
        );

        if ($optionMapping === null) {
            return new ConvertStruct(null, $this->originalData);
        }
        $this->mappingIds[] = $optionMapping['id'];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_PROPERTY_RELATION,
            $data['identifier'],
            $context
        );

        $converted = [];
        $converted['id'] = $productMapping['entityUuid'];
        $converted['properties'][] = [
            'id' => $optionMapping['entityUuid'],
        ];

        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
