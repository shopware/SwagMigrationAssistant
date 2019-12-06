<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Api\Reader;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableCountReader;

class TableCountDummyReader extends TableCountReader
{
    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        return [];
    }
}
