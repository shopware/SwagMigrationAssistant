<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Product\Aggregate\ProductPriceRule\ProductPriceRuleDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;

class PriceWriter implements WriterInterface
{
    /**
     * @var RepositoryInterface
     */
    private $productPriceRuleRepository;

    public function __construct(RepositoryInterface $productPriceRuleRepository)
    {
        $this->productPriceRuleRepository = $productPriceRuleRepository;
    }

    public function supports(): string
    {
        return ProductPriceRuleDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $this->productPriceRuleRepository->upsert($data, $context);
    }
}
