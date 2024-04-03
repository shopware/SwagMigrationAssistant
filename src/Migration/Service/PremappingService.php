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
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingReaderRegistryInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

#[Package('services-settings')]
class PremappingService implements PremappingServiceInterface
{
    public function __construct(
        private readonly PremappingReaderRegistryInterface $mappingReaderRegistry,
        private readonly MappingServiceInterface $mappingService,
        private readonly EntityRepository $mappingRepo,
        private readonly EntityRepository $runRepo,
        private readonly EntityRepository $connectionRepo
    ) {
    }

    public function generatePremapping(Context $context, MigrationContextInterface $migrationContext, SwagMigrationRunEntity $run): array
    {
        $progress = $run->getProgress();

        if ($progress === null) {
            return [];
        }

        $dataSelectionIds = \array_column($progress, 'id');
        $readers = $this->mappingReaderRegistry->getPremappingReaders($migrationContext, $dataSelectionIds);

        $preMapping = [];
        foreach ($readers as $reader) {
            $preMapping[] = $reader->getPremapping($context, $migrationContext);
        }

        return $preMapping;
    }

    public function writePremapping(Context $context, MigrationContextInterface $migrationContext, array $premapping): void
    {
        $this->mappingService->preloadMappings($this->getExistingMapping($context, $premapping), $context);
        $this->addPremappingToRun($context, $migrationContext, $premapping);
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

    private function updateConnectionPremappingStruct(MigrationContextInterface $migrationContext, array $premapping): array
    {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return [];
        }

        $connectionPremapping = $connection->getPremapping();

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
        $idSearchResult = $this->mappingRepo->searchIds($criteria, $context);

        return $idSearchResult->getIds();
    }
}
