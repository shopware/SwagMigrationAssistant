<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class FooDataSet extends DataSet
{
    public static function getEntity(): string
    {
        return 'foo';
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return true;
    }
}
