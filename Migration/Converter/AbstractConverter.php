<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Converter;

abstract class AbstractConverter implements ConverterInterface
{
    /**
     * Identifier which internal entity this converter supports
     */
    public function supports(string $profileName, string $entityName): bool
    {
        return $this->getSupportedProfileName() === $profileName
            && $this->getSupportedEntityName() === $entityName;
    }
}
