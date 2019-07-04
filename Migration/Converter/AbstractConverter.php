<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Converter;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class AbstractConverter implements ConverterInterface
{
    /**
     * Identifier which internal entity this converter supports
     */
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $this->getSupportedProfileName() === $migrationContext->getConnection()->getProfileName()
            && $this->getSupportedEntityName() === $migrationContext->getDataSet()::getEntity();
    }
}
