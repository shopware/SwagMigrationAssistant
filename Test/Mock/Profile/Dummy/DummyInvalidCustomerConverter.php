<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Profile\Dummy;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationAssistant\Test\Mock\DataSet\InvalidCustomerDataSet;

class DummyInvalidCustomerConverter extends CustomerConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getDataSet()::getEntity() === InvalidCustomerDataSet::getEntity();
    }
}
