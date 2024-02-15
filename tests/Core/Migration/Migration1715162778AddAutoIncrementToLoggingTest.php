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
use SwagMigrationAssistant\Core\Migration\Migration1715162778AddAutoIncrementToLogging;

#[CoversClass(Migration1715162778AddAutoIncrementToLogging::class)]
class Migration1715162778AddAutoIncrementToLoggingTest extends TestCase
{
    public function testUpdate(): void
    {
        $conn = KernelLifecycleManager::getConnection();

        if ($this->getAutoInrementField($conn) !== false) {
            $this->removeAutoIncrementField($conn);
        }

        $migration = new Migration1715162778AddAutoIncrementToLogging();
        $migration->update($conn);
        $migration->update($conn);

        $field = $this->getAutoInrementField($conn);
        static::assertIsArray($field, 'auto_increment field not found in table swag_migration_logging');
        static::assertArrayHasKey('Field', $field);
        static::assertSame('auto_increment', $field['Field']);
    }

    /**
     * @return array<string, mixed>|false
     */
    private function getAutoInrementField(Connection $conn): array|false
    {
        return $conn->fetchAssociative('SHOW COLUMNS FROM swag_migration_logging WHERE Field=:field;', [
            'field' => 'auto_increment',
        ]);
    }

    private function removeAutoIncrementField(Connection $conn): void
    {
        $conn->executeStatement('
            ALTER TABLE swag_migration_logging DROP COLUMN `auto_increment`;
        ');
    }
}
