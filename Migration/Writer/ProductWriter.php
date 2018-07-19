<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;

class ProductWriter implements WriterInterface
{
    /**
     * @var RepositoryInterface
     */
    private $productRepository;

    public function __construct(RepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $this->productRepository->upsert($data, $context);
    }
}
