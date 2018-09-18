<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Version;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1537433357UpdateRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1537433357;
    }

    public function update(Connection $connection): void
    {
        $this->addAdditionalData($connection);
        $this->addProfileIdColumn($connection);
        $this->addStatusColumn($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        $this->dropProfileColumn($connection);
    }

    private function addAdditionalData(Connection $connection)
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` ADD `additional_data` LONGTEXT AFTER `totals`;
SQL;
        $connection->executeQuery($sql);
    }

    private function addProfileIdColumn(Connection $connection)
    {
        // add profileId column
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` ADD `profile_id` BINARY(16) NULL DEFAULT NULL AFTER `profile`,
ADD `profile_tenant_id` BINARY(16) NULL DEFAULT NULL AFTER `profile_id`;
SQL;
        $connection->exec($sql);

        // Change profile column to nullable
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` CHANGE `profile` `profile` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
SQL;
        $connection->exec($sql);

        // Set foreign key
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` ADD FOREIGN KEY (`profile_id`) REFERENCES `swag_migration_profile`(`id`) ON DELETE SET NULL ON UPDATE NO ACTION;
SQL;
        $connection->exec($sql);

        // Get profile id from profile table
        $sql = <<<SQL
SELECT id FROM `swag_migration_profile` WHERE `profile`='shopware55' AND `gateway`='api';
SQL;
        $results = $connection->fetchAll($sql);
        if (count($results) === 0) {
            return;
        }

        $profileId = $results[0]['id'];

        // Update profile in run table
        $sql = <<<SQL
UPDATE `swag_migration_run` SET `profile_id`=:profileId WHERE `profile`='shopware55';
SQL;
        $connection->executeUpdate($sql, [
            'profileId' => $profileId,
        ]);
    }

    private function dropProfileColumn(Connection $connection)
    {
        // Drop profile column
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` DROP `profile`;
SQL;
        $connection->exec($sql);
    }

    private function addStatusColumn(Connection $connection)
    {
        // add status column
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` ADD `status` VARCHAR(255) NOT NULL DEFAULT 'finished' AFTER `additional_data`;
SQL;
        $connection->exec($sql);
    }
}
