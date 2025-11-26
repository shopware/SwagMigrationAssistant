<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Core\Migration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use SwagMigrationAssistant\Core\Migration\Migration1764145444AddFingerprintToConnectionTable;
use SwagMigrationAssistant\Test\TableHelperTrait;

#[Package('fundamentals@after-sales')]
#[CoversClass(Migration1764145444AddFingerprintToConnectionTable::class)]
class Migration1764145444AddFingerprintToConnectionTableTest extends TestCase
{
    use TableHelperTrait;

    public function testShouldAddFingerprintFieldToConnectionTable(): void
    {
        $connection = KernelLifecycleManager::getConnection();

        $table = Migration1764145444AddFingerprintToConnectionTable::TABLE;
        $column = Migration1764145444AddFingerprintToConnectionTable::COLUMN;

        static::assertTrue($this->tableExists($connection, $table));

        $this->dropColumnIfExists($connection, $table, $column);

        $migration = new Migration1764145444AddFingerprintToConnectionTable();
        $migration->update($connection);
        $migration->update($connection);

        static::assertTrue($this->columnExists($connection, $table, $column));
    }
}
