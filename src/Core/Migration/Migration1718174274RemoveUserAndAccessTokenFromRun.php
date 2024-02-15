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

/**
 * @internal
 */
#[Package('core')]
class Migration1718174274RemoveUserAndAccessTokenFromRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1718174274;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, 'swag_migration_run', 'user_id')) {
            $connection->executeStatement('ALTER TABLE `swag_migration_run` DROP COLUMN `user_id`');
        }

        if ($this->columnExists($connection, 'swag_migration_run', 'access_token')) {
            $connection->executeStatement('ALTER TABLE `swag_migration_run` DROP COLUMN `access_token`');
        }
    }
}
