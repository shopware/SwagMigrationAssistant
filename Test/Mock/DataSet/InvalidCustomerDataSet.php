<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class InvalidCustomerDataSet extends DataSet
{
    public static function getEntity(): string
    {
        return 'customerInvalid';
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return true;
    }
}
