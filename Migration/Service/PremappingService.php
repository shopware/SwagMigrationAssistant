<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Premapping\PremappingReaderRegistryInterface;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;

class PremappingService implements PremappingServiceInterface
{
    /**
     * @var PremappingReaderRegistryInterface
     */
    private $mappingReaderRegistry;

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var EntityRepositoryInterface
     */
    private $mappingRepo;

    public function __construct(
        PremappingReaderRegistryInterface $mappingReaderRegistry,
        MappingServiceInterface $mappingService,
        EntityRepositoryInterface $mappingRepo
    ) {
        $this->mappingReaderRegistry = $mappingReaderRegistry;
        $this->mappingService = $mappingService;
        $this->mappingRepo = $mappingRepo;
    }

    public function generatePremapping(Context $context, MigrationContext $migrationContext, SwagMigrationRunEntity $run): array
    {
        $dataSelectionIds = array_column($run->getProgress(), 'id');
        $readers = $this->mappingReaderRegistry->getPremappingReaders($migrationContext, $dataSelectionIds);

        $preMapping = [];
        foreach ($readers as $reader) {
            $preMapping[] = $reader->getPremapping($context, $migrationContext);
        }

        return $preMapping;
    }

    public function writePremapping(Context $context, MigrationContext $migrationContext, array $premapping): void
    {
        $this->mappingService->bulkDeleteMapping($this->getExistingMapping($context, $premapping), $context);

        foreach ($premapping as $item) {
            $entity = $item['entity'];

            foreach ($item['mapping'] as $mapping) {
                $id = $mapping['sourceId'];
                $uuid = $mapping['destinationUuid'];

                $this->mappingService->pushMapping(
                    $migrationContext->getConnection()->getId(),
                    $entity,
                    $id,
                    $uuid
                );
            }
        }

        $this->mappingService->writeMapping($context);
    }

    private function getExistingMapping(Context $context, array $premapping): array
    {
        $queries = [];
        foreach ($premapping as $item) {
            $values = [];
            foreach ($item['mapping'] as $mapping) {
                $values[] = $mapping['sourceId'];
            }

            $queries[] = new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter('entity', $item['entity']),
                    new EqualsAnyFilter('oldIdentifier', $values),
                ]
            );
        }

        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, $queries));
        /** @var IdSearchResult $idSearchResult */
        $idSearchResult = $this->mappingRepo->searchIds($criteria, $context);

        return $idSearchResult->getIds();
    }
}
