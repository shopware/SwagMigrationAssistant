<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\DataAbstractionLayer\CustomerIndexer;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;

#[Package('services-settings')]
class OrderCountIndexer extends CustomerIndexer
{
    public function __construct(
        private readonly CustomerIndexer $inner,
        private readonly Connection $connection,
    ) {
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $this->inner->handle($message);

        /** @var array<string> $ids */
        $ids = $message->getData();
        $ids = \array_unique(\array_filter($ids));

        if (empty($ids)) {
            return;
        }

        $this->updateCustomer($ids);
    }

    public function getName(): string
    {
        return $this->inner->getName();
    }

    public function iterate(?array $offset): ?EntityIndexingMessage
    {
        return $this->inner->iterate($offset);
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        return $this->inner->update($event);
    }

    public function getTotal(): int
    {
        return $this->inner->getTotal();
    }

    public function getDecorated(): CustomerIndexer
    {
        return $this->inner;
    }

    private function updateCustomer(array $ids): void
    {
        $select = 'SELECT `order_customer`.customer_id as id,
                   COUNT(`order`.id) as order_count,
                   SUM(`order`.amount_total) as order_total_amount,
                   MAX(`order`.order_date_time) as last_order_date

            FROM `order_customer`

            INNER JOIN `order`
                ON `order`.id = `order_customer`.order_id
                AND `order`.version_id = `order_customer`.order_version_id
                AND `order`.version_id = :version

            INNER JOIN `state_machine_state`
                ON `state_machine_state`.id = `order`.state_id
                AND `state_machine_state`.technical_name = :state

            WHERE `order_customer`.customer_id IN (:customerIds)
            GROUP BY `order_customer`.customer_id';
        $parameters = [
            'customerIds' => Uuid::fromHexToBytesList($ids),
            'version' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'state' => OrderStates::STATE_COMPLETED,
        ];
        $types = [
            'customerIds' => ArrayParameterType::STRING,
        ];

        $orderTotalAmounts = $this->connection->fetchAllAssociative($select, $parameters, $types);

        if (empty($orderTotalAmounts)) {
            return;
        }

        foreach ($orderTotalAmounts as $orderTotalAmount) {
            $data = [
                'id' => $orderTotalAmount['id'],
                'order_total_amount' => (float) $orderTotalAmount['order_total_amount'],
                'order_count' => (int) $orderTotalAmount['order_count'],
                'last_order_date' => $orderTotalAmount['last_order_date'],
            ];

            $this->connection->executeStatement(
                'UPDATE `customer` SET
                `customer`.`order_count` = :order_count,
                `customer`.`order_total_amount` = :order_total_amount,
                `customer`.`last_order_date` = :last_order_date
              WHERE `customer`.`id` = :id',
                $data,
                [
                    'order_count' => \PDO::PARAM_INT,
                    'order_total_amount' => \PDO::PARAM_STR,
                    'last_order_date' => \PDO::PARAM_STR,
                    'id' => \PDO::PARAM_STR,
                ]
            );
        }
    }
}
