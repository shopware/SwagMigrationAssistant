<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Gateway\Reader;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface ReaderRegistryInterface
{
    public function getReader(MigrationContextInterface $migrationContext): ReaderInterface;

    /**
     * @return ReaderInterface[]
     */
    public function getReaderForTotal(MigrationContextInterface $migrationContext): array;
}
