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
use SwagMigrationAssistant\Core\Migration\Migration1716896739RemovePremappingFromRun;

#[CoversClass(Migration1716896739RemovePremappingFromRun::class)]
class Migration1716896739RemovePremappingFromRunTest extends TestCase
{
    public function testUpdate(): void
    {
        $conn = KernelLifecycleManager::getConnection();

        if ($this->getPremappingField($conn) === false) {
            $this->addPremappingField($conn);
        }

        $migration = new Migration1716896739RemovePremappingFromRun();
        $migration->update($conn);
        $migration->update($conn);

        $field = $this->getPremappingField($conn);
        static::assertFalse($field);
    }

    /**
     * @return array<string, mixed>|false
     */
    private function getPremappingField(Connection $conn): array|false
    {
        return $conn->fetchAssociative('SHOW COLUMNS FROM `swag_migration_run` WHERE `Field`=:field;', [
            'field' => 'premapping',
        ]);
    }

    private function addPremappingField(Connection $conn): void
    {
        $conn->executeStatement('
            ALTER TABLE `swag_migration_run` ADD COLUMN `premapping` LONGTEXT;
        ');
    }
}
