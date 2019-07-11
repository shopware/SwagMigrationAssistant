<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware\DataSet;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ShopwareDataSet;

class FooDataSet extends ShopwareDataSet
{
    public static function getEntity(): string
    {
        return 'foo';
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return true;
    }

    public function getApiRoute(): string
    {
        return '';
    }

    public function getExtraQueryParameters(): array
    {
        return [];
    }
}
