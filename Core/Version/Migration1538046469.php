<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Version;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1538046469 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1538046469;
    }

    public function update(Connection $connection): void
    {
        // implement update
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
