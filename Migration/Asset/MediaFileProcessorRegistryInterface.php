<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use SwagMigrationNext\Exception\ProcessorNotFoundException;
use SwagMigrationNext\Migration\MigrationContext;

interface MediaFileProcessorRegistryInterface
{
    /**
     * @throws ProcessorNotFoundException
     */
    public function getProcessor(MigrationContext $context): MediaFileProcessorInterface;
}
