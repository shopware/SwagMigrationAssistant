<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1540217457UpdateRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1540217457;
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

    private function addAdditionalData(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` ADD `additional_data` LONGTEXT AFTER `totals`;
SQL;
        $connection->executeQuery($sql);
    }

    private function addProfileIdColumn(Connection $connection): void
    {
        $this->addProfileIdColumnToRunTable($connection);

        $this->changeProfileToNullable($connection);
        $this->setProfileIdToForeignKey($connection);

        $profileId = $this->getShopwareProfileId($connection);
        if ($profileId === false) {
            return;
        }

        $this->updateProfileIdInRunTable($connection, $profileId);
    }

    private function addStatusColumn(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` ADD `status` VARCHAR(255) NOT NULL DEFAULT 'finished' AFTER `additional_data`;
SQL;
        $connection->exec($sql);
    }

    private function dropProfileColumn(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` DROP `profile`;
SQL;
        $connection->exec($sql);
    }

    private function changeProfileToNullable(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` CHANGE `profile` `profile` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
SQL;
        $connection->exec($sql);
    }

    private function setProfileIdToForeignKey(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` ADD FOREIGN KEY (`profile_id`) REFERENCES `swag_migration_profile`(`id`) ON DELETE SET NULL ON UPDATE NO ACTION;
SQL;
        $connection->exec($sql);
    }

    /**
     * @return bool|string
     */
    private function getShopwareProfileId(Connection $connection)
    {
        $sql = <<<SQL
SELECT id FROM `swag_migration_profile` WHERE `profile`='shopware55' AND `gateway`='api';
SQL;

        return $connection->fetchColumn($sql);
    }

    private function updateProfileIdInRunTable(Connection $connection, $profileId): void
    {
        $sql = <<<SQL
UPDATE `swag_migration_run` SET `profile_id`=:profileId WHERE `profile`='shopware55';
SQL;
        $connection->executeUpdate($sql, [
            'profileId' => $profileId,
        ]);
    }

    private function addProfileIdColumnToRunTable(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` ADD `profile_id` BINARY(16) NULL DEFAULT NULL AFTER `profile`,
ADD `profile_tenant_id` BINARY(16) NULL DEFAULT NULL AFTER `profile_id`;
SQL;
        $connection->exec($sql);
    }
}
