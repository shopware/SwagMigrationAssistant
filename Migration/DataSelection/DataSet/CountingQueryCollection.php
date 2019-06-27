<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use Shopware\Core\Framework\Struct\Collection;

class CountingQueryCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return CountingQueryStruct::class;
    }
}
