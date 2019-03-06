<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\SourceContext;

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
        return CustomerGroupDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $context->scope(SourceContext::ORIGIN_SYSTEM, function (Context $context) use ($data) {
            $this->customerGroupRepository->upsert($data, $context);
        });
    }
}
