<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1537190348UpdateProfile extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1537190348;
    }

    public function update(Connection $connection): void
    {
        $this->updateApiProfiles($connection);
        $this->updateLocalProfiles($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function updateApiProfiles(Connection $connection)
    {
        $sql = <<<SQL
SELECT id, credential_fields FROM swag_migration_profile WHERE profile=:profile AND gateway=:gateway;
SQL;
        $results = $connection->fetchAll($sql, ['profile' => 'shopware55', 'gateway' => 'api']);
        if (count($results) === 0) {
            return;
        }

        foreach ($results as $oldProfile) {
            $oldCredentials = json_decode($oldProfile['credential_fields'], true);
            $newCredentials = json_encode([
                'endpoint' => [
                    'value' => $oldCredentials['endpoint'],
                    'type' => 'text',
                    'required' => true,
                ],
                'apiUser' => [
                    'value' => $oldCredentials['apiUser'],
                    'type' => 'text',
                    'required' => true,
                ],
                'apiKey' => [
                    'value' => $oldCredentials['apiKey'],
                    'type' => 'text',
                    'required' => true,
                ],
            ]);

            $sql = <<<SQL
UPDATE swag_migration_profile SET credential_fields=:credentialFields WHERE id=:id;
SQL;

            $connection->executeUpdate($sql, [
                'credentialFields' => $newCredentials,
                'id' => $oldProfile['id'],
            ]);
        }
    }

    private function updateLocalProfiles(Connection $connection)
    {
        $sql = <<<SQL
SELECT id, credential_fields FROM swag_migration_profile WHERE profile=:profile AND gateway=:gateway;
SQL;
        $results = $connection->fetchAll($sql, ['profile' => 'shopware55', 'gateway' => 'local']);
        if (count($results) === 0) {
            return;
        }

        foreach ($results as $oldProfile) {
            $oldCredentials = json_decode($oldProfile['credential_fields'], true);
            $newCredentials = json_encode([
                'dbHost' => [
                    'value' => $oldCredentials['dbHost'],
                    'type' => 'text',
                    'required' => true,
                ],
                'dbPort' => [
                    'value' => ($oldCredentials['dbPort'] === '' ? 3306 : $oldCredentials['dbPort']),
                    'type' => 'number',
                    'required' => true,
                ],
                'dbName' => [
                    'value' => $oldCredentials['dbName'],
                    'type' => 'text',
                    'required' => true,
                ],
                'dbUser' => [
                    'value' => $oldCredentials['dbUser'],
                    'type' => 'text',
                    'required' => true,
                ],
                'dbPassword' => [
                    'value' => $oldCredentials['dbPassword'],
                    'type' => 'password',
                    'required' => false,
                ],
            ]);

            $sql = <<<SQL
UPDATE swag_migration_profile SET credential_fields=:credentialFields WHERE id=:id;
SQL;

            $connection->executeUpdate($sql, [
                'credentialFields' => $newCredentials,
                'id' => $oldProfile['id'],
            ]);
        }
    }
}
