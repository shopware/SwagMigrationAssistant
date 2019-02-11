<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Converter;

use SwagMigrationNext\Migration\MigrationContextInterface;

interface ConverterRegistryInterface
{
    /**
     * Returns the converter which supports the given internal entity
     */
    public function getConverter(MigrationContextInterface $context): ConverterInterface;
}
