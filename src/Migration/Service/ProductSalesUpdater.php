<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;

#[Package('fundamentals@after-sales')]
final readonly class ProductSalesUpdater
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param array<string> $orderIds
     *
     * @return array<string>
     */
    public function getProductIdsForOrders(array $orderIds): array
    {
        $orderIds = $this->filterIds($orderIds);

        if ($orderIds === []) {
            return [];
        }

        $sql = <<<'SQL'
            SELECT DISTINCT order_line_item.product_id
            FROM order_line_item
            WHERE order_line_item.order_id IN (:orderIds)
                AND order_line_item.version_id = :liveVersion
                AND order_line_item.order_version_id = :liveVersion
                AND order_line_item.product_version_id = :liveVersion
                AND order_line_item.type = :lineItemType
                AND order_line_item.product_id IS NOT NULL
        SQL;

        $productIds = $this->connection->fetchFirstColumn(
            $sql,
            [
                'orderIds' => Uuid::fromHexToBytesList($orderIds),
                'liveVersion' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'lineItemType' => LineItem::PRODUCT_LINE_ITEM_TYPE,
            ],
            [
                'orderIds' => ArrayParameterType::BINARY,
            ]
        );

        return \array_map(
            static fn (string $productId): string => Uuid::fromBytesToHex($productId),
            $productIds
        );
    }

    /**
     * Recalculates the denormalized product.sales value from persisted live order line items.
     *
     * @param array<string> $productIds
     */
    public function updateProducts(array $productIds): void
    {
        $productIds = $this->filterIds($productIds);

        if ($productIds === []) {
            return;
        }

        $productByteIds = Uuid::fromHexToBytesList($productIds);

        $parameters = [
            'productIds' => $productByteIds,
            'outerProductIds' => $productByteIds,
            'liveVersion' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'lineItemType' => LineItem::PRODUCT_LINE_ITEM_TYPE,
            'cancelledState' => OrderStates::STATE_CANCELLED,
        ];

        $types = [
            'productIds' => ArrayParameterType::BINARY,
            'outerProductIds' => ArrayParameterType::BINARY,
        ];

        $sql = <<<'SQL'
            UPDATE product
            LEFT JOIN (
                SELECT order_line_item.product_id, SUM(order_line_item.quantity) AS sales
                FROM order_line_item
                INNER JOIN `order`
                    ON `order`.id = order_line_item.order_id
                    AND `order`.version_id = order_line_item.order_version_id
                    AND `order`.version_id = :liveVersion
                INNER JOIN state_machine_state
                    ON state_machine_state.id = `order`.state_id
                WHERE order_line_item.product_id IN (:productIds)
                    AND order_line_item.version_id = :liveVersion
                    AND order_line_item.product_version_id = :liveVersion
                    AND order_line_item.type = :lineItemType
                    AND state_machine_state.technical_name != :cancelledState
                GROUP BY order_line_item.product_id
            ) product_sales
                ON product_sales.product_id = product.id
            SET product.sales = COALESCE(product_sales.sales, 0),
                product.updated_at = NOW()
            WHERE product.id IN (:outerProductIds)
                AND product.version_id = :liveVersion
        SQL;

        RetryableQuery::retryable($this->connection, function () use ($sql, $parameters, $types): void {
            $this->connection->executeStatement($sql, $parameters, $types);
        });
    }

    /**
     * @param array<string> $ids
     *
     * @return array<string>
     */
    private function filterIds(array $ids): array
    {
        $filteredIds = \array_filter($ids, static fn (string $id): bool => Uuid::isValid($id));

        return \array_unique($filteredIds);
    }
}
