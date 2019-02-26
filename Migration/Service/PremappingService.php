<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
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

    public function __construct(PremappingReaderRegistryInterface $mappingReaderRegistry, MappingServiceInterface $mappingService)
    {
        $this->mappingReaderRegistry = $mappingReaderRegistry;
        $this->mappingService = $mappingService;
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
        foreach ($premapping as $item) {
            $entity = $item['entity'];

            foreach ($item['mapping'] as $mapping) {
                $id = $mapping['sourceId'];
                $uuid = $mapping['destinationUuid'];

                $this->mappingService->createNewUuid(
                    $migrationContext->getConnection()->getId(),
                    $entity,
                    $id,
                    $context,
                    null,
                    $uuid
                );
            }
        }

        $this->mappingService->writeMapping($context);
    }
}
