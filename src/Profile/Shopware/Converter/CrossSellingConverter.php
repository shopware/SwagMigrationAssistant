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

#[Package('fundamentals@after-sales')]
abstract class CrossSellingConverter extends ShopwareConverter
{
    protected Context $context;

    protected string $connectionId;

    protected string $runId;

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'] . '_' . $data['type'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->runId = $migrationContext->getRunUuid();

        $connection = $migrationContext->getConnection();
        $this->connectionId = $connection->getId();

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CROSS_SELLING,
            $this->getSourceIdentifier($data),
            $context,
            $this->checksum
        );

        $crossSellingMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            $data['type'],
            $data['articleID'],
            $context,
            $this->checksum
        );

        $converted['id'] = $crossSellingMapping['entityId'];
        $sourceProductMapping = $this->getProductMapping($data['articleID']);

        $sourceProductId = null;

        if ($sourceProductMapping !== null) {
            $this->mappingIds[] = $sourceProductMapping['id'];
            $sourceProductId = $sourceProductMapping['entityId'];
        }

        $relatedProductMapping = $this->getProductMapping($data['relatedarticle']);
        $relatedProductId = null;

        if ($relatedProductMapping !== null) {
            $this->mappingIds[] = $relatedProductMapping['id'];
            $relatedProductId = $relatedProductMapping['entityId'];
        }

        if ($data['type'] === DefaultEntities::CROSS_SELLING_SIMILAR) {
            $converted['name'] = 'Similar Items';
        } else {
            $converted['name'] = 'Accessory Items';
        }

        $relationMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            $data['type'] . '_relation',
            $data['articleID'] . '_' . $data['relatedarticle'],
            $context
        );

        $converted['type'] = 'productList';
        $converted['active'] = true;
        $converted['productId'] = $sourceProductId;
        $converted['assignedProducts'] = [
            [
                'id' => $relationMapping['entityId'] ?? null,
                'position' => $data['position'] ?? null,
                'productId' => $relatedProductId,
            ],
        ];

        unset(
            $data['type'],
            $data['id'],
            $data['articleID'],
            $data['relatedarticle'],
            $data['position']
        );

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getProductMapping(string $identifier): ?array
    {
        $productMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_MAIN,
            $identifier,
            $this->context
        );

        if ($productMapping === null) {
            $productMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_CONTAINER,
                $identifier,
                $this->context
            );
        }

        return $productMapping;
    }
}
