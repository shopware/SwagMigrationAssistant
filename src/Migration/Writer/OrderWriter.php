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
        // The migration payload still contains serialized transaction amounts.
        // Convert them back to price structs before the DAL writes the orders.
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

        // Read the current products for the target orders before the upsert.
        // In a re-migration, a line item can switch from product A to product B,
        // so product A would no longer be reachable after the write.
        $orderIdsBeforeWrite = $this->extractOrderIdsFromPayload($data);
        $productIdsBeforeWrite = $this->productSalesUpdater->getProductIdsForOrders($orderIdsBeforeWrite);

        // Let the regular writer create new orders or update already migrated orders.
        $result = parent::writeData($data, $context);

        // Collect all written order IDs. The payload IDs cover existing orders,
        // while the DAL write result also confirms newly inserted orders.
        $writtenOrderIds = $this->extractOrderIdsFromWriteResults($result);
        $orderIds = $this->merge($orderIdsBeforeWrite, $writtenOrderIds);

        // Read the products again after the write to catch new or changed line items.
        $productIdsAfterWrite = $this->productSalesUpdater->getProductIdsForOrders($orderIds);
        $affectedProductIds = $this->merge($productIdsBeforeWrite, $productIdsAfterWrite);

        // Recalculate the final sales value for every affected product.
        // The updater derives the value from persisted line items, so repeated
        // migration runs replace the sales value instead of incrementing it again.
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

            $id = $primaryKey['id'];

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
