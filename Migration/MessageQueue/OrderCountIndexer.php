<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class OrderCountIndexer extends EntityIndexer
{
    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly EntityIndexer $inner
    ) {
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $this->inner->handle($message);

        /** @var array<array<string>|string> $ids */
        $ids = $message->getData();
        $ids = \array_unique(\array_filter($ids));

        if (empty($ids)) {
            return;
        }

        $data = [];
        $context = $message->getContext();

        $criteria = new Criteria($ids);
        $criteria->addAssociation('orderCustomers');
        $searchResult = $this->customerRepository->search($criteria, $context);

        /** @var CustomerEntity $customer */
        foreach ($searchResult->getEntities() as $customer) {
            $orderCount = 0;
            $orderCustomers = $customer->getOrderCustomers();
            if ($orderCustomers !== null) {
                $orderCount = $orderCustomers->count();
            }
            $data[] = [
                'id' => $customer->getId(),
                'orderCount' => $orderCount,
            ];
        }

        $context->scope(Context::SYSTEM_SCOPE, function () use ($data, $context): void {
            $this->customerRepository->update($data, $context);
        });
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

    public function getDecorated(): EntityIndexer
    {
        return $this->inner;
    }
}
