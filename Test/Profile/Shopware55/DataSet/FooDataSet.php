<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\DataSet;

use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\Shopware55DataSet;

class FooDataSet extends Shopware55DataSet
{
    public static function getEntity(): string
    {
        return 'foo';
    }

    public function supports(string $profileName, string $entity): bool
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
