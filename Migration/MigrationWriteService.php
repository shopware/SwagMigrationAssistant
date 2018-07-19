<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
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
        foreach (EntityRelationMapping::getMapping($migrationContext->getEntityName()) as $entity) {
            $criteria = new Criteria();
            $criteria->addFilter(new TermQuery('entityName', $entity));
            $migrationData = $this->migrationDataRepository->search($criteria, $context);

            $converted = [];
            array_map(function ($data) use (&$converted) {
                /* @var ArrayStruct $data */
                $converted[] = array_filter($data->get('converted'));
            }, $migrationData->getElements());

            $currentWriter = $this->writerRegistry->getWriter($entity);
            $currentWriter->writeData($converted, $context);
        }
    }
}
