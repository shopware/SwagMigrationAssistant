<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

interface ConverterRegistryInterface
{
    public function getConverter(string $entity): ConverterInterface;
}
