<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Category\Util\CategoryPathBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;

class CategoryWriter implements WriterInterface
{
    /**
     * @var RepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var CategoryPathBuilder
     */
    private $categoryPathBuilder;

    public function __construct(RepositoryInterface $categoryRepository, CategoryPathBuilder $categoryPathBuilder)
    {
        $this->categoryRepository = $categoryRepository;
        $this->categoryPathBuilder = $categoryPathBuilder;
    }

    public function supports(): string
    {
        return CategoryDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $this->categoryRepository->upsert($data, $context);
    }
}
