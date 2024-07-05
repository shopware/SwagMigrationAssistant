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
class Migration1716896739RemovePremappingFromRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1716896739;
    }

    public function update(Connection $connection): void
    {
        if (!$this->columnExists($connection, 'swag_migration_run', 'premapping')) {
            return;
        }

        $connection->executeStatement('ALTER TABLE `swag_migration_run` DROP COLUMN `premapping`');
    }
}
