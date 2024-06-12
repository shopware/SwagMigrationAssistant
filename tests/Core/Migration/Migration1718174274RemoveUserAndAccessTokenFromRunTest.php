<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Core\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use SwagMigrationAssistant\Core\Migration\Migration1718174274RemoveUserAndAccessTokenFromRun;

#[CoversClass(Migration1718174274RemoveUserAndAccessTokenFromRun::class)]
class Migration1718174274RemoveUserAndAccessTokenFromRunTest extends TestCase
{
    public function testUpdate(): void
    {
        $conn = KernelLifecycleManager::getConnection();

        // setup database in old (before migration) state
        if ($this->getRunField($conn, 'user_id') === false) {
            $this->addUserIdField($conn);
        }
        if ($this->getRunField($conn, 'access_token') === false) {
            $this->addAccessTokenField($conn);
        }

        // run migrations
        $migration = new Migration1718174274RemoveUserAndAccessTokenFromRun();
        $migration->update($conn);
        $migration->update($conn);

        // assert the columns were removed
        $userIdField = $this->getRunField($conn, 'user_id');
        static::assertFalse($userIdField);
        $accessTokenField = $this->getRunField($conn, 'access_token');
        static::assertFalse($accessTokenField);
    }

    /**
     * @return array<string, mixed>|false
     */
    private function getRunField(Connection $conn, string $field): array|false
    {
        return $conn->fetchAssociative('SHOW COLUMNS FROM `swag_migration_run` WHERE `Field`=:field;', [
            'field' => $field,
        ]);
    }

    private function addUserIdField(Connection $conn): void
    {
        $conn->executeStatement('
            ALTER TABLE `swag_migration_run` ADD COLUMN `user_id` VARCHAR(255) NULL;
        ');
    }

    private function addAccessTokenField(Connection $conn): void
    {
        $conn->executeStatement('
            ALTER TABLE `swag_migration_run` ADD COLUMN `access_token` VARCHAR(255) NULL;
        ');
    }
}
