<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;

abstract class ShopwareDataSet extends DataSet
{
    abstract public function getApiRoute(): string;

    abstract public function getExtraQueryParameters(): array;
}
