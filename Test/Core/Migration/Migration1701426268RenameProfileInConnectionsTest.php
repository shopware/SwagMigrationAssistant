<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Core\Migration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Core\Migration\Migration1701426268RenameProfileInConnections;

/**
 * @covers \SwagMigrationAssistant\Core\Migration\Migration1701426268RenameProfileInConnections
 */
class Migration1701426268RenameProfileInConnectionsTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    public function testUpdate(): void
    {
        $connection = KernelLifecycleManager::getConnection();

        $connectionId = Uuid::randomBytes();
        $connection->insert('swag_migration_connection', [
            'id' => $connectionId,
            'name' => 'test',
            'profile_name' => 'shopware63',
            'gateway_name' => 'api',
            'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $migration = new Migration1701426268RenameProfileInConnections();
        $migration->update($connection);

        $newProfileName = $connection->fetchOne(
            'SELECT profile_name FROM swag_migration_connection WHERE id = :id',
            ['id' => $connectionId]
        );
        static::assertSame('shopware6major', $newProfileName);
    }
}
