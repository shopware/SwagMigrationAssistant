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
class Migration1715162778AddAutoIncrementToLogging extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1715162778;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, 'swag_migration_logging', 'auto_increment')) {
            return;
        }

        $connection->executeStatement('
            ALTER TABLE `swag_migration_logging` ADD `auto_increment` BIGINT unsigned NOT NULL AUTO_INCREMENT,
            ADD KEY `idx.auto_increment` (`auto_increment`);
        ');
    }
}
