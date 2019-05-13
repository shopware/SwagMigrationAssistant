<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Profile\Dummy;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationAssistant\Test\Mock\DataSet\InvalidCustomerDataSet;

class DummyInvalidCustomerConverter extends CustomerConverter
{
    public function supports(string $profileName, DataSet $dataSet): bool
    {
        return $dataSet::getEntity() === InvalidCustomerDataSet::getEntity();
    }
}
