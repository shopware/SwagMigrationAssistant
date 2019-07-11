<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingReaderRegistryInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

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

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $connectionRepo;

    public function __construct(
        PremappingReaderRegistryInterface $mappingReaderRegistry,
        MappingServiceInterface $mappingService,
        EntityRepositoryInterface $mappingRepo,
        EntityRepositoryInterface $runRepo,
        EntityRepositoryInterface $connectionRepo
    ) {
        $this->mappingReaderRegistry = $mappingReaderRegistry;
        $this->mappingService = $mappingService;
        $this->mappingRepo = $mappingRepo;
        $this->runRepo = $runRepo;
        $this->connectionRepo = $connectionRepo;
    }

    public function generatePremapping(Context $context, MigrationContextInterface $migrationContext, SwagMigrationRunEntity $run): array
    {
        $dataSelectionIds = array_column($run->getProgress(), 'id');
        $readers = $this->mappingReaderRegistry->getPremappingReaders($migrationContext, $dataSelectionIds);

        $preMapping = [];
        foreach ($readers as $reader) {
            $preMapping[] = $reader->getPremapping($context, $migrationContext);
        }

        return $preMapping;
    }

    public function writePremapping(Context $context, MigrationContextInterface $migrationContext, array $premapping): void
    {
        $this->mappingService->bulkDeleteMapping($this->getExistingMapping($context, $premapping), $context);
        $this->addPremappingToRun($context, $migrationContext, $premapping);
        $this->updateConnectionPremapping($context, $migrationContext, $premapping);

        foreach ($premapping as $item) {
            $entity = $item['entity'];

            foreach ($item['mapping'] as $mapping) {
                $id = $mapping['sourceId'];
                $identifier = $mapping['destinationUuid'];

                if (Uuid::isValid($identifier)) {
                    $this->mappingService->pushMapping(
                        $migrationContext->getConnection()->getId(),
                        $entity,
                        $id,
                        $identifier
                    );
                    continue;
                }

                $this->mappingService->pushValueMapping(
                    $migrationContext->getConnection()->getId(),
                    $entity,
                    $id,
                    $identifier
                );
            }
        }

        $this->mappingService->writeMapping($context);
    }

    private function updateConnectionPremapping(Context $context, MigrationContextInterface $migrationContext, array $premapping): void
    {
        $premapping = $this->updateConnectionPremappingStruct($migrationContext, $premapping);

        $this->connectionRepo->update(
            [
                [
                    'id' => $migrationContext->getConnection()->getId(),
                    'premapping' => $premapping,
                ],
            ],
            $context
        );
    }

    private function updateConnectionPremappingStruct(MigrationContextInterface $migrationContext, array $premapping): array
    {
        $connectionPremapping = $migrationContext->getConnection()->getPremapping();

        if ($connectionPremapping === null) {
            return $premapping;
        }

        foreach ($premapping as $newPremappingItem) {
            $inConnection = false;
            foreach ($connectionPremapping as $key => $premappingItem) {
                if ($premappingItem['entity'] === $newPremappingItem['entity']) {
                    $inConnection = true;
                    $connectionPremapping[$key] = $newPremappingItem;
                }
            }

            if (!$inConnection) {
                $connectionPremapping[] = $newPremappingItem;
            }
        }

        return $connectionPremapping;
    }

    private function addPremappingToRun(Context $context, MigrationContextInterface $migrationContext, array $premapping): void
    {
        $this->runRepo->update(
            [
                [
                    'id' => $migrationContext->getRunUuid(),
                    'premapping' => $premapping,
                ],
            ],
            $context
        );
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
