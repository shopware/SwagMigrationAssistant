<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Profile\Dummy;

use SwagMigrationNext\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationNext\Test\Mock\DataSet\InvalidCustomerDataSet;

class DummyInvalidCustomerConverter extends CustomerConverter
{
    public function supports(string $profileName, DataSet $dataSet): bool
    {
        return $dataSet::getEntity() === InvalidCustomerDataSet::getEntity();
    }
}
