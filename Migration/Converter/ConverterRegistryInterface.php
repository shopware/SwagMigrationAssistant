<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Converter;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface ConverterRegistryInterface
{
    /**
     * Returns the converter which supports the given internal entity
     */
    public function getConverter(MigrationContextInterface $context): ConverterInterface;
}
