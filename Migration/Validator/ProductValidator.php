<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Validator;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;

class ProductValidator implements ValidatorInterface
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

    public function validateData(array $data, Context $context): void
    {
        //Todo: Validate the data
    }
}
