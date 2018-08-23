<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use Shopware\Core\Framework\ORM\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\ArrayStruct;
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
            /* @var ArrayStruct $data */
            $value = $data->get('converted');
            if ($value !== null) {
                $converted[] = array_filter($value);
            }
        }, $migrationData->getElements());

        $currentWriter = $this->writerRegistry->getWriter($entity);
        $currentWriter->writeData($converted, $context);
    }
}
