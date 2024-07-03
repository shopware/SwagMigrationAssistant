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
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Core\Migration\Migration1720008983TruncateOutdatedProgressStructureFromRun;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;

#[CoversClass(Migration1720008983TruncateOutdatedProgressStructureFromRun::class)]
class Migration1720008983TruncateOutdatedProgressStructureFromRunTest extends TestCase
{
    protected function tearDown(): void
    {
        $conn = KernelLifecycleManager::getConnection();
        $conn->createQueryBuilder()
            ->delete(SwagMigrationRunDefinition::ENTITY_NAME)
            ->executeStatement();
    }

    public function testUpdate(): void
    {
        $conn = KernelLifecycleManager::getConnection();

        // setup database in old (before migration) state
        $runIdWithOldProgress = Uuid::randomHex();
        $runIdWithNoProgress = Uuid::randomHex();
        $runIdWithNewProgress = Uuid::randomHex();
        $runNewProgress = [
            'currentEntityProgress' => 42,
            'progress' => 43,
            'total' => 44,
        ];
        $this->addRun($conn, [
            'id' => Uuid::fromHexToBytes($runIdWithOldProgress),
            'step' => 'finished',
            'progress' => [
                [
                    'extensions' => [],
                    'id' => 'basicSettings',
                    'entities' => [
                        [
                            'extensions' => [],
                            'entityName' => 'language',
                            'currentCount' => 0,
                            'total' => 0,
                        ],
                        [
                            'extensions' => [],
                            'entityName' => 'category_custom_field',
                            'currentCount' => 0,
                            'total' => 6,
                        ],
                        'currentCount' => 0,
                    ],
                    'total' => 77,
                    'processMediaFiles' => true,
                    'snippet' => 'swag-migration.index.selectDataCard.dataSelection.basicSettings',
                ],
            ],
        ]);
        $this->addRun($conn, [
            'id' => Uuid::fromHexToBytes($runIdWithNoProgress),
            'step' => 'finished',
            'progress' => null,
        ]);
        $this->addRun($conn, [
            'id' => Uuid::fromHexToBytes($runIdWithNewProgress),
            'step' => 'finished',
            'progress' => $runNewProgress,
        ]);

        // run migrations
        $migration = new Migration1720008983TruncateOutdatedProgressStructureFromRun();
        $migration->update($conn);
        $migration->update($conn);

        // compare run progress
        static::assertNull($this->getRunProgress($conn, $runIdWithOldProgress));
        static::assertNull($this->getRunProgress($conn, $runIdWithNoProgress));
        static::assertSame(\json_encode($runNewProgress), $this->getRunProgress($conn, $runIdWithNewProgress));
    }

    /**
     * @param array{id: string, step: string, progress: array<mixed>|null} $run
     */
    private function addRun(Connection $conn, array $run): void
    {
        $conn->createQueryBuilder()
            ->insert(SwagMigrationRunDefinition::ENTITY_NAME)
            ->values([
                'id' => ':id',
                'step' => ':step',
                'progress' => ':progress',
                'created_at' => 'NOW()',
            ])
            ->setParameter('id', $run['id'])
            ->setParameter('step', $run['step'])
            ->setParameter('progress', \json_encode($run['progress']))
            ->executeStatement();
    }

    private function getRunProgress(Connection $conn, string $runId): string|false|null
    {
        return $conn->createQueryBuilder()
            ->select('progress')
            ->from(SwagMigrationRunDefinition::ENTITY_NAME)
            ->where('id = :id')
            ->setParameter('id', Uuid::fromHexToBytes($runId))
            ->fetchOne();
    }
}
