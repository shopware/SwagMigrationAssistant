<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;

abstract class Shopware55DataSet extends DataSet
{
    abstract public function getApiRoute(): string;

    abstract public function getExtraQueryParameters(): array;
}
