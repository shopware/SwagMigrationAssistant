<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\EntityRepository;

class ProductWriter implements WriterInterface
{

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    public function __construct(EntityRepository $entityRepository)
    {
        $this->entityRepository = $entityRepository;
    }

    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $this->entityRepository->create($data, $context);
    }
}