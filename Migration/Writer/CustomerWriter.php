<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class CustomerWriter implements WriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $customerRepository;

    public function __construct(EntityRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function supports(): string
    {
        return CustomerDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $this->customerRepository->upsert($data, $context);
    }
}
