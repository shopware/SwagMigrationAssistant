<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet;

use SwagMigrationNext\Migration\DataSelection\DataSet\DataSet;

abstract class Shopware55DataSet extends DataSet
{
    abstract public function getApiRoute(): string;

    abstract public function getExtraQueryParameters(): array;
}
