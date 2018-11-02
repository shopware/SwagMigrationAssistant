<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;

class CustomerWriter implements WriterInterface
{
    /**
     * @var RepositoryInterface
     */
    private $customerRepository;

    public function __construct(RepositoryInterface $customerRepository)
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
