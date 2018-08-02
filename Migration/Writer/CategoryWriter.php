<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Category\Util\CategoryPathBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\Write\EntityWriterInterface;
use Shopware\Core\Framework\ORM\Write\WriteContext;

class CategoryWriter implements WriterInterface
{
    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    /**
     * @var CategoryPathBuilder
     */
    private $categoryPathBuilder;

    public function __construct(EntityWriterInterface $entityWriter, CategoryPathBuilder $categoryPathBuilder)
    {
        $this->entityWriter = $entityWriter;
        $this->categoryPathBuilder = $categoryPathBuilder;
    }

    public function supports(): string
    {
        return CategoryDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $this->entityWriter->upsert(
            CategoryDefinition::class,
            $data,
            WriteContext::createFromContext($context)
        );

        foreach (array_column($data, 'id') as $categoryId) {
            $this->categoryPathBuilder->update($categoryId, $context);
        }
    }
}
