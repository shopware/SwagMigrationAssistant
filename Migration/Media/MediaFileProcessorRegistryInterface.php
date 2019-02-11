<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Media;

use SwagMigrationNext\Exception\ProcessorNotFoundException;
use SwagMigrationNext\Migration\MigrationContextInterface;

interface MediaFileProcessorRegistryInterface
{
    /**
     * @throws ProcessorNotFoundException
     */
    public function getProcessor(MigrationContextInterface $context): MediaFileProcessorInterface;
}
