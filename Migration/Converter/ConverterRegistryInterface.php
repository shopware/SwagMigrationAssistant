<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Converter;

interface ConverterRegistryInterface
{
    /**
     * Returns the converter which supports the given internal entity
     */
    public function getConverter(string $entity): ConverterInterface;
}
