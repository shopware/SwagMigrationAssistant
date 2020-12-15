<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class OrderCountIndexer extends EntityIndexer
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var EntityIndexer
     */
    private $inner;

    public function __construct(
        EntityRepositoryInterface $repository,
        EntityIndexer $inner
    ) {
        $this->customerRepository = $repository;
        $this->inner = $inner;
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $this->inner->handle($message);

        $ids = $message->getData();
        $ids = \array_unique(\array_filter($ids));

        if (empty($ids)) {
            return;
        }

        $data = [];
        $context = $message->getContext();

        foreach ($ids as $customerId) {
            $customerResult = $this->customerRepository->search(
                (new Criteria([$customerId]))->addAssociation('orderCustomers'),
                $context
            );

            /** @var CustomerEntity|null $customer */
            $customer = $customerResult->first();
            if ($customer === null) {
                continue;
            }

            $orderCount = 0;
            $orderCustomer = $customer->getOrderCustomers();
            if ($orderCustomer !== null) {
                $orderCount = $orderCustomer->count();
            }

            $data[] = [
                'id' => $customerId,
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

    /**
     * @param int $offset
     */
    public function iterate($offset): ?EntityIndexingMessage
    {
        return $this->inner->iterate($offset);
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        return $this->inner->update($event);
    }
}
