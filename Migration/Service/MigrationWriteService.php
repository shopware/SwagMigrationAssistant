<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use Shopware\Core\Framework\ORM\Search\Sorting\FieldSorting;
use SwagMigrationNext\Migration\Data\SwagMigrationDataStruct;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Writer\WriterRegistryInterface;

class MigrationWriteService implements MigrationWriteServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var WriterRegistryInterface[]
     */
    private $writerRegistry;

    public function __construct(RepositoryInterface $migrationDataRepo, WriterRegistryInterface $writerRegistry)
    {
        $this->migrationDataRepo = $migrationDataRepo;
        $this->writerRegistry = $writerRegistry;
    }

    public function writeData(MigrationContext $migrationContext, Context $context): void
    {
        $entity = $migrationContext->getEntity();
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entity', $entity));
        $criteria->addFilter(new TermQuery('runId', $migrationContext->getRunUuid()));
        $criteria->setOffset($migrationContext->getOffset());
        $criteria->setLimit($migrationContext->getLimit());
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        $migrationData = $this->migrationDataRepo->search($criteria, $context);

        if ($migrationData->getTotal() === 0) {
            return;
        }

        $converted = [];
        $updateWrittenData = [];
        array_map(function ($data) use (&$converted, &$updateWrittenData) {
            /* @var SwagMigrationDataStruct $data */
            $value = $data->getConverted();
            if ($value !== null) {
                $converted[] = $value;
                $updateWrittenData[] = [
                    'id' => $data->getId(),
                    'written' => true,
                ];
            }
        }, $migrationData->getElements());

        if (empty($converted)) {
            return;
        }

        $currentWriter = $this->writerRegistry->getWriter($entity);
        $currentWriter->writeData($converted, $context);

        $this->migrationDataRepo->update($updateWrittenData, $context);
    }
}
