<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

#[Package('services-settings')]
class Migration1587476616DeleteOldMappings extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1587476616;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
DELETE swag_migration_mapping FROM swag_migration_mapping
INNER JOIN swag_migration_connection ON profile_name IN ('shopware54', 'shopware55', 'shopware56')
WHERE entity = 'main_product_filter' OR entity = 'main_product_options'
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
