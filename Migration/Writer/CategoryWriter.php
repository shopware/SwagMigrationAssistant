<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class CategoryWriter implements WriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $categoryRepository;

    public function __construct(EntityRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
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
