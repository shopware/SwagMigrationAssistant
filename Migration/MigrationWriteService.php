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
    private $migrationDataRepository;

    /**
     * @var WriterRegistryInterface[]
     */
    private $writerRegistry;

    public function __construct(
        EntityRepository $migrationDataRepository,
        WriterRegistryInterface $writerRegistry
    ) {
        $this->migrationDataRepository = $migrationDataRepository;
        $this->writerRegistry = $writerRegistry;
    }

    public function writeData(MigrationContext $migrationContext, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entityName', $migrationContext->getEntityName()));
        $migrationData = $this->migrationDataRepository->search($criteria, $context);

        $converted = [];
        array_map(function ($data) use (&$converted) {
            $converted[] = array_filter($data->get('converted'));
        }, $migrationData->getElements());

        $currentWriter = $this->writerRegistry->getWriter($migrationContext->getEntityName());
        $currentWriter->writeData($converted, $context);
    }
}
