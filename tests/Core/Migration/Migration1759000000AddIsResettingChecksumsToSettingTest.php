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
use SwagMigrationAssistant\Core\Migration\Migration1759000000AddIsResettingChecksumsToSetting;
use SwagMigrationAssistant\Test\TableHelperTrait;

#[Package('fundamentals@after-sales')]
#[CoversClass(Migration1759000000AddIsResettingChecksumsToSetting::class)]
class Migration1759000000AddIsResettingChecksumsToSettingTest extends TestCase
{
    use TableHelperTrait;

    public function testShouldAddIsResettingChecksumsFieldToSettingTable(): void
    {
        $connection = KernelLifecycleManager::getConnection();

        $table = Migration1759000000AddIsResettingChecksumsToSetting::TABLE;
        $column = Migration1759000000AddIsResettingChecksumsToSetting::COLUMN;

        static::assertTrue($this->tableExists($connection, $table));

        $this->dropColumnIfExists($connection, $table, $column);

        $migration = new Migration1759000000AddIsResettingChecksumsToSetting();
        $migration->update($connection);
        $migration->update($connection);

        static::assertTrue($this->columnExists($connection, $table, $column));
    }
}
