<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Converter;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;

abstract class AbstractConverter implements ConverterInterface
{
    /**
     * Identifier which internal entity this converter supports
     */
    public function supports(string $profileName, DataSet $dataSet): bool
    {
        return $this->getSupportedProfileName() === $profileName
            && $this->getSupportedEntityName() === $dataSet::getEntity();
    }
}
