<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Writer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Service\ProductSalesUpdater;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;
use SwagMigrationAssistant\Migration\Writer\OrderWriter;

#[Package('fundamentals@after-sales')]
class OrderWriterTest extends TestCase
{
    private EntityWriterInterface&MockObject $entityWriter;

    private Connection&MockObject $connection;

    private OrderWriter $orderWriter;

    protected function setUp(): void
    {
        $this->entityWriter = $this->createMock(EntityWriterInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->orderWriter = new OrderWriter(
            $this->entityWriter,
            new OrderDefinition(),
            $this->createMock(StructNormalizer::class),
            new ProductSalesUpdater($this->connection)
        );
    }

    public function testWriteDataUpdatesProductSalesForPersistedOrderLineItems(): void
    {
        $orderId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $orderData = [
            [
                'id' => $orderId,
                'lineItems' => [
                    [
                        'productId' => $productId,
                        'quantity' => 3,
                    ],
                ],
            ],
        ];
        $writeResult = [
            OrderDefinition::ENTITY_NAME => [
                new EntityWriteResult(
                    $orderId,
                    [],
                    OrderDefinition::ENTITY_NAME,
                    EntityWriteResult::OPERATION_INSERT
                ),
            ],
        ];

        $this->entityWriter->expects($this->once())
            ->method('upsert')
            ->willReturn($writeResult);

        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                static::callback(static fn (string $sql): bool => \str_contains($sql, 'UPDATE product')),
                static::callback(static fn (array $parameters): bool => self::containsProductIds($parameters, [$productId])),
                [
                    'productIds' => ArrayParameterType::BINARY,
                    'outerProductIds' => ArrayParameterType::BINARY,
                ]
            )
            ->willReturn(1);

        $this->connection->expects($this->exactly(2))
            ->method('fetchFirstColumn')
            ->willReturnOnConsecutiveCalls([], [Uuid::fromHexToBytes($productId)]);

        $result = $this->orderWriter->writeData($orderData, $context);

        static::assertSame($writeResult, $result);
        static::assertTrue($context->hasExtension(AbstractWriter::EXTENSION_NAME));
    }

    public function testWriteDataExcludesCancelledOrdersFromProductSales(): void
    {
        $orderId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $writeResult = [
            OrderDefinition::ENTITY_NAME => [
                new EntityWriteResult(
                    $orderId,
                    [],
                    OrderDefinition::ENTITY_NAME,
                    EntityWriteResult::OPERATION_INSERT
                ),
            ],
        ];

        $this->entityWriter->expects($this->once())
            ->method('upsert')
            ->willReturn($writeResult);

        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                static::callback(static fn (string $sql): bool => \str_contains($sql, 'state_machine_state.technical_name != :cancelledState')),
                static::callback(static fn (array $parameters): bool => self::containsProductIds($parameters, [$productId])
                    && ($parameters['cancelledState'] ?? null) === OrderStates::STATE_CANCELLED),
                [
                    'productIds' => ArrayParameterType::BINARY,
                    'outerProductIds' => ArrayParameterType::BINARY,
                ]
            )
            ->willReturn(1);

        $this->connection->expects($this->exactly(2))
            ->method('fetchFirstColumn')
            ->willReturnOnConsecutiveCalls([], [Uuid::fromHexToBytes($productId)]);

        $this->orderWriter->writeData([['id' => $orderId]], $context);
    }

    public function testWriteDataUpdatesPreviousAndCurrentProductsWhenExistingLineItemChangesProduct(): void
    {
        $orderId = Uuid::randomHex();
        $previousProductId = Uuid::randomHex();
        $currentProductId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $writeResult = [
            OrderDefinition::ENTITY_NAME => [
                new EntityWriteResult(
                    $orderId,
                    [],
                    OrderDefinition::ENTITY_NAME,
                    EntityWriteResult::OPERATION_UPDATE
                ),
            ],
        ];

        $this->entityWriter->expects($this->once())
            ->method('upsert')
            ->willReturn($writeResult);

        $this->connection->expects($this->exactly(2))
            ->method('fetchFirstColumn')
            ->willReturnOnConsecutiveCalls(
                [Uuid::fromHexToBytes($previousProductId)],
                [Uuid::fromHexToBytes($currentProductId)]
            );

        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                static::callback(static fn (string $sql): bool => \str_contains($sql, 'UPDATE product')),
                static::callback(static fn (array $parameters): bool => self::containsProductIds($parameters, [$previousProductId, $currentProductId])),
                [
                    'productIds' => ArrayParameterType::BINARY,
                    'outerProductIds' => ArrayParameterType::BINARY,
                ]
            )
            ->willReturn(1);

        $this->orderWriter->writeData([
            [
                'id' => $orderId,
                'lineItems' => [
                    [
                        'productId' => $currentProductId,
                    ],
                ],
            ],
        ], $context);
    }

    /**
     * @param array<string, list<string>|string> $parameters
     * @param list<string> $expectedProductIds
     */
    private static function containsProductIds(array $parameters, array $expectedProductIds): bool
    {
        $productIds = $parameters['productIds'] ?? null;

        if (!\is_array($productIds)) {
            return false;
        }

        $actualProductIds = \array_map(
            static fn (string $productId): string => Uuid::fromBytesToHex($productId),
            $productIds
        );

        \sort($actualProductIds);
        \sort($expectedProductIds);

        return $actualProductIds === $expectedProductIds;
    }
}
