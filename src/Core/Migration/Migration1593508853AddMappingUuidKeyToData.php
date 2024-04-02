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
class Migration1593508853AddMappingUuidKeyToData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1593508853;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE swag_migration_data ADD INDEX `idx.swag_migration_data.mapping_uuid` (`mapping_uuid`);');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
