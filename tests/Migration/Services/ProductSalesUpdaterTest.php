<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Services;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Integration\Builder\Order\OrderBuilder;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use SwagMigrationAssistant\Migration\Service\ProductSalesUpdater;

/**
 * @phpstan-type OrderLineItemFixture array{
 *     id: string,
 *     identifier: string,
 *     referencedId: string,
 *     productId: string,
 *     productVersionId: string,
 *     quantity: int,
 *     type: string,
 *     label: string,
 *     price: CalculatedPrice,
 *     priceDefinition: QuantityPriceDefinition,
 *     payload: array{productNumber: string},
 *     good: bool,
 *     removable: bool,
 *     stackable: bool,
 *     position: int
 * }
 */
#[Package('fundamentals@after-sales')]
class ProductSalesUpdaterTest extends TestCase
{
    use BasicTestDataBehaviour;
    use IntegrationTestBehaviour;

    private Connection $connection;

    private ProductSalesUpdater $productSalesUpdater;

    private Context $context;

    /**
     * @var EntityRepository<OrderCollection>
     */
    private EntityRepository $orderRepository;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $this->orderRepository = static::getContainer()->get('order.repository');
        $this->context = Context::createDefaultContext();
        $this->productSalesUpdater = new ProductSalesUpdater($this->connection);
    }

    public function testUpdatesProductSalesFromPersistedOrderLineItems(): void
    {
        $soldProductId = Uuid::randomHex();
        $cancelledProductId = Uuid::randomHex();
        $refundedProductId = Uuid::randomHex();
        $transactionCancelledProductId = Uuid::randomHex();

        $this->createProduct($soldProductId);
        $this->createProduct($cancelledProductId);
        $this->createProduct($refundedProductId);
        $this->createProduct($transactionCancelledProductId);

        $openOrderId = $this->createOrderWithProductLineItem($soldProductId, 3, OrderStates::STATE_OPEN);
        $cancelledOrderId = $this->createOrderWithProductLineItem($cancelledProductId, 7, OrderStates::STATE_CANCELLED);
        $refundedOrderId = $this->createOrderWithProductLineItem($refundedProductId, 5, OrderStates::STATE_COMPLETED, OrderTransactionStates::STATE_REFUNDED);
        $transactionCancelledOrderId = $this->createOrderWithProductLineItem($transactionCancelledProductId, 11, OrderStates::STATE_COMPLETED, OrderTransactionStates::STATE_CANCELLED);

        static::assertEqualsCanonicalizing(
            [$soldProductId, $cancelledProductId, $refundedProductId, $transactionCancelledProductId],
            $this->productSalesUpdater->getProductIdsForOrders([$openOrderId, $cancelledOrderId, $refundedOrderId, $transactionCancelledOrderId, 'invalid', $openOrderId])
        );

        $productIds = [$soldProductId, $cancelledProductId, $refundedProductId, $transactionCancelledProductId, 'invalid', $soldProductId];

        $this->productSalesUpdater->updateProducts($productIds);

        static::assertSame(3, $this->fetchProductSales($soldProductId));
        static::assertSame(0, $this->fetchProductSales($cancelledProductId));
        static::assertSame(0, $this->fetchProductSales($refundedProductId));
        static::assertSame(0, $this->fetchProductSales($transactionCancelledProductId));

        // A remigration can process the same order data again. The updater recalculates
        // from persisted line items and must not increment the existing sales values.
        $this->productSalesUpdater->updateProducts($productIds);

        static::assertSame(3, $this->fetchProductSales($soldProductId));
        static::assertSame(0, $this->fetchProductSales($cancelledProductId));
        static::assertSame(0, $this->fetchProductSales($refundedProductId));
        static::assertSame(0, $this->fetchProductSales($transactionCancelledProductId));
    }

    public function testUpdateSumsProductSalesAcrossMultipleOrders(): void
    {
        $productId = Uuid::randomHex();

        $this->createProduct($productId);

        $firstOrderId = $this->createOrderWithProductLineItem($productId, 3, OrderStates::STATE_OPEN);
        $secondOrderId = $this->createOrderWithProductLineItem($productId, 2, OrderStates::STATE_COMPLETED);
        $cancelledOrderId = $this->createOrderWithProductLineItem($productId, 9, OrderStates::STATE_CANCELLED);

        $this->updateProductsForOrders([$firstOrderId, $secondOrderId, $cancelledOrderId]);

        static::assertSame(5, $this->fetchProductSales($productId));
    }

    public function testRemigrationWithChangedQuantityRecalculatesProductSales(): void
    {
        $productId = Uuid::randomHex();

        $this->createProduct($productId);

        $orderId = $this->createOrderWithProductLineItem($productId, 3, OrderStates::STATE_OPEN);

        $this->updateProductsForOrders([$orderId]);

        static::assertSame(3, $this->fetchProductSales($productId));

        // During remigration the persisted line item can be overwritten. The next
        // updater run must use the new quantity, not add it to the previous sales value.
        $this->updateOrderLineItemQuantity($orderId, $productId, 5);
        $this->updateProductsForOrders([$orderId]);

        static::assertSame(5, $this->fetchProductSales($productId));
    }

    private function createProduct(string $productId): void
    {
        $productNumber = 'product-sales-' . $productId;

        $this->connection->insert('product', [
            'id' => Uuid::fromHexToBytes($productId),
            'version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'product_number' => $productNumber,
            'active' => 1,
            'stock' => 10,
            'available_stock' => 10,
            'available' => 1,
            'sales' => 99,
            'child_count' => 0,
            'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    private function createOrderWithProductLineItem(
        string $productId,
        int $quantity,
        string $state,
        string $transactionState = OrderTransactionStates::STATE_OPEN
    ): string {
        $ids = new IdsCollection();
        $orderKey = 'order-' . Uuid::randomHex();
        $transactionKey = 'transaction-' . Uuid::randomHex();
        $lineItemId = Uuid::randomHex();

        $order = (new OrderBuilder($ids, $orderKey))
            ->add('stateId', $this->getStateMachineState(OrderStates::STATE_MACHINE, $state))
            ->add('primaryOrderTransactionId', $ids->get($transactionKey))
            ->addTransaction($transactionKey, [
                'stateId' => $this->getStateMachineState(OrderTransactionStates::STATE_MACHINE, $transactionState),
            ])
            ->add('lineItems', [$this->createProductLineItem($lineItemId, $productId, $quantity)])
            ->build();

        $this->orderRepository->create([$order], $this->context);

        return $ids->get($orderKey);
    }

    private function updateOrderLineItemQuantity(string $orderId, string $productId, int $quantity): void
    {
        $affectedRows = $this->connection->update('order_line_item', [
            'quantity' => $quantity,
        ], [
            'order_id' => Uuid::fromHexToBytes($orderId),
            'order_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'product_id' => Uuid::fromHexToBytes($productId),
            'product_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
        ]);

        static::assertSame(1, $affectedRows);
    }

    /**
     * @param array<string> $orderIds
     */
    private function updateProductsForOrders(array $orderIds): void
    {
        $this->productSalesUpdater->updateProducts(
            $this->productSalesUpdater->getProductIdsForOrders($orderIds)
        );
    }

    /**
     * @return OrderLineItemFixture
     */
    private function createProductLineItem(string $lineItemId, string $productId, int $quantity): array
    {
        $unitPrice = 10.0;

        return [
            'id' => $lineItemId,
            'identifier' => $productId,
            'referencedId' => $productId,
            'productId' => $productId,
            'productVersionId' => Defaults::LIVE_VERSION,
            'quantity' => $quantity,
            'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
            'label' => 'Product sales test product',
            'price' => new CalculatedPrice(
                $unitPrice,
                $unitPrice * $quantity,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                $quantity
            ),
            'priceDefinition' => new QuantityPriceDefinition($unitPrice, new TaxRuleCollection(), $quantity),
            'payload' => ['productNumber' => 'product-sales-' . $productId],
            'good' => true,
            'removable' => true,
            'stackable' => true,
            'position' => 1,
        ];
    }

    private function fetchProductSales(string $productId): int
    {
        $sales = $this->connection->fetchOne('
            SELECT sales
            FROM product
            WHERE id = :productId
                AND version_id = :liveVersion
        ', [
            'productId' => Uuid::fromHexToBytes($productId),
            'liveVersion' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
        ]);

        static::assertNotFalse($sales);

        return (int) $sales;
    }
}
