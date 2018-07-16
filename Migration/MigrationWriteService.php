<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\EntityRepository;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use SwagMigrationNext\Migration\Writer\WriterRegistryInterface;

class MigrationWriteService implements MigrationWriteServiceInterface
{
    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @var WriterRegistryInterface[]
     */
    private $writerRegistry;

    public function __construct(
        EntityRepository $entityRepository,
        WriterRegistryInterface $writerRegistry
    )
    {
        $this->entityRepository = $entityRepository;
        $this->writerRegistry = $writerRegistry;
    }

    public function writeData(MigrationContext $migrationContext, Context $context): void {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entityType', $migrationContext->getEntityType()));
        $migration_data = $this->entityRepository->search($criteria, $context);

        $currentWriter = $this->writerRegistry->getWriter($migrationContext->getEntityType());
        $currentWriter->writeData($migration_data->getElements(), $context);
    }
}