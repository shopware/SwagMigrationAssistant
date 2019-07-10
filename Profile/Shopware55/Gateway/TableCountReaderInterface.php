<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface TableCountReaderInterface
{
    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array;
}
