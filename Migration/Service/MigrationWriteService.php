<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use Shopware\Core\Framework\ORM\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\ArrayStruct;
use SwagMigrationNext\Migration\Data\SwagMigrationDataStruct;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Writer\WriterRegistryInterface;

class MigrationWriteService implements MigrationWriteServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepository;

    /**
     * @var WriterRegistryInterface[]
     */
    private $writerRegistry;

    public function __construct(
        RepositoryInterface $migrationDataRepository,
        WriterRegistryInterface $writerRegistry
    ) {
        $this->migrationDataRepository = $migrationDataRepository;
        $this->writerRegistry = $writerRegistry;
    }

    public function writeData(MigrationContext $migrationContext, Context $context): void
    {
        $entity = $migrationContext->getEntity();
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entity', $entity));
        $criteria->addFilter(new TermQuery('profile', $migrationContext->getProfile()));
        $criteria->setOffset($migrationContext->getOffset());
        $criteria->setLimit($migrationContext->getLimit());
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        $migrationData = $this->migrationDataRepository->search($criteria, $context);

        if ($migrationData->getTotal() === 0) {
            return;
        }

        $converted = [];
        array_map(function ($data) use (&$converted) {
            /* @var SwagMigrationDataStruct $data */
            $value = $data->getConverted();
            if ($value !== null) {
                $converted[] = $value;
            }
        }, $migrationData->getElements());

        if (empty($converted)) {
            return;
        }

        $currentWriter = $this->writerRegistry->getWriter($entity);
        $currentWriter->writeData($converted, $context);
    }
}
