<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Premapping;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

class PremappingReaderRegistry implements PremappingReaderRegistryInterface
{
    /**
     * @var PremappingReaderInterface[]
     */
    private $preMappingReaders;

    public function __construct(iterable $preMappingReaders)
    {
        $this->preMappingReaders = $preMappingReaders;
    }

    /**
     * {@inheritdoc}
     */
    public function getPremappingReaders(MigrationContextInterface $migrationContext, array $dataSelectionIds): array
    {
        $profileName = $migrationContext->getConnection()->getProfileName();
        $gatewayName = $migrationContext->getConnection()->getGatewayName();

        $preMapping = [];
        foreach ($this->preMappingReaders as $preMappingReader) {
            if ($preMappingReader->supports($profileName, $gatewayName, $dataSelectionIds)) {
                $preMapping[] = $preMappingReader;
            }
        }

        return $preMapping;
    }
}
