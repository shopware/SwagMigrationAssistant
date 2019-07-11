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
        $preMapping = [];
        foreach ($this->preMappingReaders as $preMappingReader) {
            if ($preMappingReader->supports($migrationContext, $dataSelectionIds)) {
                $preMapping[] = $preMappingReader;
            }
        }

        return $preMapping;
    }
}
