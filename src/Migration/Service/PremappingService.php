<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingReaderRegistryInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;

/**
 * @phpstan-type PremappingArrayStructure array<array{entity: string, mapping: list<array{sourceId: string, description: string, destinationUuid: string}>}>
 */
#[Package('services-settings')]
class PremappingService implements PremappingServiceInterface
{
    /**
     * @param EntityRepository<SwagMigrationMappingCollection> $mappingRepo
     * @param EntityRepository<SwagMigrationConnectionCollection> $connectionRepo
     */
    public function __construct(
        private readonly PremappingReaderRegistryInterface $mappingReaderRegistry,
        private readonly MappingServiceInterface $mappingService,
        private readonly EntityRepository $mappingRepo,
        private readonly EntityRepository $connectionRepo
    ) {
    }

    /**
     * @param list<string> $dataSelectionIds
     *
     * @return array<int, PremappingStruct>
     */
    public function generatePremapping(Context $context, MigrationContextInterface $migrationContext, array $dataSelectionIds): array
    {
        $readers = $this->mappingReaderRegistry->getPremappingReaders($migrationContext, $dataSelectionIds);

        $preMapping = [];
        foreach ($readers as $reader) {
            $preMapping[] = $reader->getPremapping($context, $migrationContext);
        }

        return $preMapping;
    }

    /**
     * @param PremappingArrayStructure $premapping
     */
    public function writePremapping(Context $context, MigrationContextInterface $migrationContext, array $premapping): void
    {
        $this->mappingService->preloadMappings($this->getExistingMapping($context, $premapping), $context);
        $this->updateConnectionPremapping($context, $migrationContext, $premapping);

        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return;
        }

        foreach ($premapping as $item) {
            $entity = $item['entity'];

            foreach ($item['mapping'] as $mapping) {
                $id = $mapping['sourceId'];
                $identifier = $mapping['destinationUuid'];

                if (!isset($identifier) || $identifier === '') {
                    continue;
                }

                if (Uuid::isValid($identifier)) {
                    $this->mappingService->getOrCreateMapping(
                        $connection->getId(),
                        $entity,
                        $id,
                        $context,
                        null,
                        null,
                        $identifier
                    );

                    continue;
                }

                $this->mappingService->getOrCreateMapping(
                    $connection->getId(),
                    $entity,
                    $id,
                    $context,
                    null,
                    null,
                    null,
                    $identifier
                );
            }
        }

        $this->mappingService->writeMapping($context);
    }

    /**
     * @param PremappingArrayStructure $premapping
     */
    private function updateConnectionPremapping(Context $context, MigrationContextInterface $migrationContext, array $premapping): void
    {
        $premapping = $this->updateConnectionPremappingStruct($migrationContext, $premapping);

        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return;
        }

        $this->connectionRepo->update(
            [
                [
                    'id' => $connection->getId(),
                    'premapping' => $premapping,
                ],
            ],
            $context
        );
    }

    /**
     * @param PremappingArrayStructure $premapping
     *
     * @return array<PremappingStruct>
     */
    private function updateConnectionPremappingStruct(MigrationContextInterface $migrationContext, array $premapping): array
    {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return [];
        }

        $connectionPremapping = $connection->getPremapping();

        if ($connectionPremapping === null) {
            $connectionPremapping = [];
        }

        foreach ($premapping as $newPremappingItem) {
            $inConnection = false;
            foreach ($connectionPremapping as $key => $premappingItem) {
                if ($premappingItem->getEntity() === $newPremappingItem['entity']) {
                    $mappings = [];
                    foreach ($newPremappingItem['mapping'] as $mapping) {
                        $mappings[] = new PremappingEntityStruct(
                            $mapping['sourceId'],
                            $mapping['description'],
                            $mapping['destinationUuid']
                        );
                    }

                    $inConnection = true;
                    $connectionPremapping[$key] = new PremappingStruct($newPremappingItem['entity'], $mappings);
                }
            }

            if (!$inConnection) {
                $mappings = [];
                foreach ($newPremappingItem['mapping'] as $mapping) {
                    $mappings[] = new PremappingEntityStruct(
                        $mapping['sourceId'],
                        $mapping['description'],
                        $mapping['destinationUuid']
                    );
                }

                $connectionPremapping[] = new PremappingStruct($newPremappingItem['entity'], $mappings);
            }
        }

        return $connectionPremapping;
    }

    /**
     * @param PremappingArrayStructure $premapping
     *
     * @return list<string>|list<array<string, string>>
     */
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
        $idSearchResult = $this->mappingRepo->searchIds($criteria, $context);

        return $idSearchResult->getIds();
    }
}
