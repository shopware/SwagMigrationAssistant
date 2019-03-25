<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;

class CustomerGroupWriter implements WriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $customerGroupRepository;

    public function __construct(EntityRepositoryInterface $customerGroupRepository)
    {
        $this->customerGroupRepository = $customerGroupRepository;
    }

    public function supports(): string
    {
        return DefaultEntities::CUSTOMER_GROUP;
    }

    public function writeData(array $data, Context $context): void
    {
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data) {
            $this->customerGroupRepository->upsert($data, $context);
        });
    }
}
