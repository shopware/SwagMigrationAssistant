<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;

class ProductManufacturerWriter implements WriterInterface
{
    /**
     * @var RepositoryInterface
     */
    private $productManufacturerRepository;

    public function __construct(RepositoryInterface $productManufacturerRepository)
    {
        $this->productManufacturerRepository = $productManufacturerRepository;
    }

    public function supports(): string
    {
        return ProductManufacturerDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $this->productManufacturerRepository->upsert($data, $context);
    }
}
