<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Service\ProductSalesUpdater;

#[Package('fundamentals@after-sales')]
class OrderWriter extends AbstractWriter
{
    public function __construct(
        EntityWriterInterface $entityWriter,
        EntityDefinition $definition,
        private readonly StructNormalizer $structNormalizer,
        private readonly ProductSalesUpdater $productSalesUpdater,
    ) {
        parent::__construct($entityWriter, $definition);
    }

    public function supports(): string
    {
        return DefaultEntities::ORDER;
    }

    public function writeData(array $data, Context $context): array
    {
        foreach ($data as &$item) {
            if (!isset($item['transactions']) || !\is_array($item['transactions'])) {
                continue;
            }

            foreach ($item['transactions'] as &$transaction) {
                $transaction['amount'] = $this->structNormalizer->denormalize($transaction['amount']);
            }
            unset($transaction);
        }
        unset($item);

        // Re-migration case: product A is only visible before a line item switches to product B.
        $orderIdsBeforeWrite = $this->extractOrderIdsFromPayload($data);
        $productIdsBeforeWrite = $this->productSalesUpdater->getProductIdsForOrders($orderIdsBeforeWrite);

        // Write new orders or update already migrated orders.
        $result = parent::writeData($data, $context);

        // Merge order IDs from payload and write result to also cover inserted orders.
        $writtenOrderIds = $this->extractOrderIdsFromWriteResults($result);
        $orderIds = $this->merge($orderIdsBeforeWrite, $writtenOrderIds);
        $productIdsAfterWrite = $this->productSalesUpdater->getProductIdsForOrders($orderIds);
        $affectedProductIds = $this->merge($productIdsBeforeWrite, $productIdsAfterWrite);

        // Recalculate every product that was affected before or after write.
        $this->productSalesUpdater->updateProducts($affectedProductIds);

        return $result;
    }

    /**
     * Reads the target order IDs from the converted migration payload before the DAL upsert.
     * These IDs are needed to find products that may disappear from an existing order during re-migration.
     *
     * @param array<array-key, array{id?: string|null}> $payload
     *
     * @return list<string>
     */
    private function extractOrderIdsFromPayload(array $payload): array
    {
        $orderIds = [];

        foreach ($payload as $item) {
            $id = $item['id'] ?? null;

            if (\is_string($id)) {
                $orderIds[] = $id;
            }
        }

        return \array_values(\array_unique($orderIds));
    }

    /**
     * @param array<string, array<EntityWriteResult>> $writeResults
     *
     * @return list<string>
     */
    private function extractOrderIdsFromWriteResults(array $writeResults): array
    {
        $orderIds = [];

        foreach ($writeResults[OrderDefinition::ENTITY_NAME] ?? [] as $writeResult) {
            $primaryKey = $writeResult->getPrimaryKey();

            if (\is_string($primaryKey)) {
                $orderIds[] = $primaryKey;

                continue;
            }

            $id = $primaryKey['id'] ?? null;

            if (\is_string($id)) {
                $orderIds[] = $id;
            }
        }

        return \array_values(\array_unique($orderIds));
    }

    /**
     * @param array<string> $arrayOne
     * @param array<string> $arrayTwo
     *
     * @return list<string>
     */
    private function merge(array $arrayOne, array $arrayTwo): array
    {
        return \array_values(\array_unique(\array_merge($arrayOne, $arrayTwo)));
    }
}
