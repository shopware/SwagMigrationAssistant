<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;


use IteratorAggregate;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\EntityRepository;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use SwagMigrationNext\Migration\Writer\ProductWriter;

class MigrationWriteService implements MigrationWriteServiceInterface
{
    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @var ProductWriter[]
     */
    private $writers;

    public function __construct(EntityRepository $entityRepository,
                                IteratorAggregate $writers)
    {
        $this->entityRepository = $entityRepository;
        $this->writers = $writers;
    }

    public function writeData(MigrationContext $migrationContext, Context $context): void {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entityType', $migrationContext->getEntityType()));
        $migration_data = $this->entityRepository->search($criteria, $context);

        $currentWriter = null;
        foreach ($this->writers as $writer) {
            if ($writer->supports() === $migrationContext->getEntityType()) {
                $currentWriter = $writer;
                break;
            }
        }

        $currentWriter->writeData($migration_data->getElements(), $context);
    }
}