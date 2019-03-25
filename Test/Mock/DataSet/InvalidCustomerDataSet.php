<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\DataSet;

use SwagMigrationNext\Migration\DataSelection\DataSet\DataSet;

class InvalidCustomerDataSet extends DataSet
{
    public static function getEntity(): string
    {
        return 'customerInvalid';
    }

    public function supports(string $profileName, string $entity): bool
    {
        return true;
    }
}
