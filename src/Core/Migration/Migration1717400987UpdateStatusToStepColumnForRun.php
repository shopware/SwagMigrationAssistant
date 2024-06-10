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
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;

/**
 * @internal
 */
#[Package('core')]
class Migration1717400987UpdateStatusToStepColumnForRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1717400987;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, SwagMigrationRunDefinition::ENTITY_NAME, 'step')) {
            return;
        }

        $connection->executeStatement('ALTER TABLE `swag_migration_run` CHANGE `status` `step` VARCHAR(255) NOT NULL');

        /**
         * Migrate data from old status [running, aborted, finished]
         * to new step enum, which also includes [aborted, finished] but doesn't include "running".
         *
         * Normal users shouldn't have a migration "running" while performing a plugin update (very bad!),
         * but to not leave the migration in a very broken state we migrate these runs to "aborted" anyway.
         *
         * Also migrate any other unknown values to "aborted"
         */
        $connection->executeStatement('
            UPDATE `swag_migration_run` SET `step` = "aborted" WHERE `step` != "finished" AND `step` != "aborted"
        ');
    }
}
