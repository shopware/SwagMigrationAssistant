<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;

class OrderAttributeWriter implements WriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $attributeSetRepository;

    public function __construct(EntityRepositoryInterface $attributeSetRepository)
    {
        $this->attributeSetRepository = $attributeSetRepository;
    }

    public function supports(): string
    {
        return DefaultEntities::ORDER_ATTRIBUTE;
    }

    public function writeData(array $data, Context $context): void
    {
        $this->attributeSetRepository->upsert($data, $context);
    }
}
