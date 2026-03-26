<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\History;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\History\LogGroupingService;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(LogGroupingService::class)]
class LogGroupingServiceTest extends TestCase
{
    private Connection&MockObject $connection;

    private LogGroupingService $logGroupingService;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->logGroupingService = new LogGroupingService($this->connection);
    }

    public function testGetGroupedLogsByCodeAndEntityThrowsExceptionWhenConnectionNotFound(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        static::expectException(MigrationException::class);
        static::expectExceptionMessage('No connection found.');

        $this->logGroupingService->getGroupedLogsByCodeAndEntity(
            Uuid::randomHex(),
            'error',
            1,
            10,
            'count',
            'DESC',
            null,
            null,
            null,
            null
        );
    }

    /**
     * @param array<int, array<string, mixed>> $dbRows
     * @param array<int, array<string, mixed>> $levelCountRows
     * @param array<int, array{code: string, entityName: string|null, fieldName: string|null, profileName: string, gatewayName: string, count: int, fixCount: int, isPreviouslyFixed: bool}> $expectedItems
     * @param array{error: int, warning: int, info: int} $expectedLevelCounts
     */
    #[DataProvider('getGroupedLogsDataProvider')]
    public function testGetGroupedLogsByCodeAndEntityMapsResultsCorrectly(
        array $dbRows,
        array $levelCountRows,
        int $expectedTotal,
        array $expectedItems,
        array $expectedLevelCounts,
    ): void {
        $connectionId = Uuid::randomBytes();

        $this->connection->method('fetchOne')->willReturn($connectionId);

        $mainResult = $this->createMock(Result::class);
        $mainResult->method('fetchAllAssociative')->willReturn($dbRows);

        $levelResult = $this->createMock(Result::class);
        $levelResult->method('fetchAllAssociative')->willReturn($levelCountRows);

        $this->connection->method('executeQuery')
            ->willReturnOnConsecutiveCalls($mainResult, $levelResult);

        $result = $this->logGroupingService->getGroupedLogsByCodeAndEntity(
            Uuid::randomHex(),
            'error',
            1,
            10,
            'count',
            'DESC',
            null,
            null,
            null,
            null
        );

        static::assertSame($expectedTotal, $result['total']);
        static::assertSame($expectedItems, $result['items']);
        static::assertSame($expectedLevelCounts, $result['levelCounts']);
    }

    /**
     * @return iterable<string, array{dbRows: array<int, array<string, mixed>>, levelCountRows: array<int, array<string, mixed>>, expectedTotal: int, expectedItems: array<int, array{code: string, entityName: string|null, fieldName: string|null, profileName: string, gatewayName: string, count: int, fixCount: int, isPreviouslyFixed: bool}>, expectedLevelCounts: array{error: int, warning: int, info: int}}>
     */
    public static function getGroupedLogsDataProvider(): iterable
    {
        yield 'empty results' => [
            'dbRows' => [],
            'levelCountRows' => [],
            'expectedTotal' => 0,
            'expectedItems' => [],
            'expectedLevelCounts' => ['error' => 0, 'warning' => 0, 'info' => 0],
        ];

        yield 'single log entry' => [
            'dbRows' => [
                [
                    'code' => 'MISSING_REQUIRED_FIELD',
                    'entity_name' => 'product',
                    'field_name' => 'name',
                    'profile_name' => 'shopware55',
                    'gateway_name' => 'local',
                    'count' => '5',
                    'total' => '1',
                    'fix_count' => '2',
                    'is_previously_fixed' => '0',
                ],
            ],
            'levelCountRows' => [
                ['level' => 'error', 'count' => '1'],
            ],
            'expectedTotal' => 1,
            'expectedItems' => [
                [
                    'code' => 'MISSING_REQUIRED_FIELD',
                    'entityName' => 'product',
                    'fieldName' => 'name',
                    'profileName' => 'shopware55',
                    'gatewayName' => 'local',
                    'count' => 5,
                    'fixCount' => 2,
                    'isPreviouslyFixed' => false,
                ],
            ],
            'expectedLevelCounts' => ['error' => 1, 'warning' => 0, 'info' => 0],
        ];

        yield 'multiple log entries with all levels and previously fixed entry' => [
            'dbRows' => [
                [
                    'code' => 'MISSING_REQUIRED_FIELD',
                    'entity_name' => 'product',
                    'field_name' => 'name',
                    'profile_name' => 'shopware55',
                    'gateway_name' => 'local',
                    'count' => '10',
                    'total' => '3',
                    'fix_count' => '5',
                    'is_previously_fixed' => '0',
                ],
                [
                    'code' => 'INVALID_FORMAT',
                    'entity_name' => 'customer',
                    'field_name' => 'email',
                    'profile_name' => 'shopware55',
                    'gateway_name' => 'api',
                    'count' => '3',
                    'total' => '3',
                    'fix_count' => '0',
                    'is_previously_fixed' => '0',
                ],
                [
                    'code' => 'DEPRECATED_FIELD',
                    'entity_name' => null,
                    'field_name' => null,
                    'profile_name' => 'shopware6',
                    'gateway_name' => 'local',
                    'count' => '1',
                    'total' => '3',
                    'fix_count' => '1',
                    'is_previously_fixed' => '1',
                ],
            ],
            'levelCountRows' => [
                ['level' => 'ERROR', 'count' => '2'],
                ['level' => 'WARNING', 'count' => '5'],
                ['level' => 'INFO', 'count' => '10'],
            ],
            'expectedTotal' => 3,
            'expectedItems' => [
                [
                    'code' => 'MISSING_REQUIRED_FIELD',
                    'entityName' => 'product',
                    'fieldName' => 'name',
                    'profileName' => 'shopware55',
                    'gatewayName' => 'local',
                    'count' => 10,
                    'fixCount' => 5,
                    'isPreviouslyFixed' => false,
                ],
                [
                    'code' => 'INVALID_FORMAT',
                    'entityName' => 'customer',
                    'fieldName' => 'email',
                    'profileName' => 'shopware55',
                    'gatewayName' => 'api',
                    'count' => 3,
                    'fixCount' => 0,
                    'isPreviouslyFixed' => false,
                ],
                [
                    'code' => 'DEPRECATED_FIELD',
                    'entityName' => null,
                    'fieldName' => null,
                    'profileName' => 'shopware6',
                    'gatewayName' => 'local',
                    'count' => 1,
                    'fixCount' => 1,
                    'isPreviouslyFixed' => true,
                ],
            ],
            'expectedLevelCounts' => ['error' => 2, 'warning' => 5, 'info' => 10],
        ];

        yield 'level counts with unknown level are ignored' => [
            'dbRows' => [
                [
                    'code' => 'TEST_CODE',
                    'entity_name' => 'order',
                    'field_name' => 'status',
                    'profile_name' => 'shopware55',
                    'gateway_name' => 'local',
                    'count' => '1',
                    'total' => '1',
                    'fix_count' => '0',
                    'is_previously_fixed' => '0',
                ],
            ],
            'levelCountRows' => [
                ['level' => 'error', 'count' => '3'],
                ['level' => 'unknown_level', 'count' => '99'],
                ['level' => 'warning', 'count' => '2'],
            ],
            'expectedTotal' => 1,
            'expectedItems' => [
                [
                    'code' => 'TEST_CODE',
                    'entityName' => 'order',
                    'fieldName' => 'status',
                    'profileName' => 'shopware55',
                    'gatewayName' => 'local',
                    'count' => 1,
                    'fixCount' => 0,
                    'isPreviouslyFixed' => false,
                ],
            ],
            'expectedLevelCounts' => ['error' => 3, 'warning' => 2, 'info' => 0],
        ];
    }

    /**
     * @param array<int, array<string, string>> $dbResult
     * @param array<string> $expectedEntityIds
     */
    #[DataProvider('getLogEntityIdsWithoutFixDataProvider')]
    public function testGetLogEntityIdsWithoutFixByCodeAndEntity(
        array $dbResult,
        ?string $connectionId,
        array $expectedEntityIds,
    ): void {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($dbResult);

        $this->connection->method('executeQuery')->willReturn($result);

        $ids = $this->logGroupingService->getLogEntityIdsWithoutFixByCodeAndEntity(
            Uuid::randomHex(),
            'MISSING_FIELD',
            'product',
            'name',
            5,
            $connectionId
        );

        static::assertSame($expectedEntityIds, $ids);
    }

    /**
     * @return iterable<string, array{dbResult: array<int, array<string, string>>, connectionId: string|null, expectedEntityIds: array<string>}>
     */
    public static function getLogEntityIdsWithoutFixDataProvider(): iterable
    {
        $id1 = Uuid::randomHex();
        $id2 = Uuid::randomHex();
        $id3 = Uuid::randomHex();

        yield 'empty results without connectionId' => [
            'dbResult' => [],
            'connectionId' => null,
            'expectedEntityIds' => [],
        ];

        yield 'empty results with connectionId' => [
            'dbResult' => [],
            'connectionId' => Uuid::randomHex(),
            'expectedEntityIds' => [],
        ];

        yield 'single result without connectionId' => [
            'dbResult' => [['entity_id' => $id1]],
            'connectionId' => null,
            'expectedEntityIds' => [$id1],
        ];

        yield 'single result with connectionId' => [
            'dbResult' => [['entity_id' => $id1]],
            'connectionId' => Uuid::randomHex(),
            'expectedEntityIds' => [$id1],
        ];

        yield 'multiple results' => [
            'dbResult' => [
                ['entity_id' => $id1],
                ['entity_id' => $id2],
                ['entity_id' => $id3],
            ],
            'connectionId' => Uuid::randomHex(),
            'expectedEntityIds' => [$id1, $id2, $id3],
        ];

        yield 'empty connectionId treated as null' => [
            'dbResult' => [['entity_id' => $id1]],
            'connectionId' => '',
            'expectedEntityIds' => [$id1],
        ];
    }

    #[DataProvider('sortColumnValidationProvider')]
    public function testSortColumnValidation(
        string $inputSortBy,
        string $expectedColumnInQuery,
    ): void {
        $connectionId = Uuid::randomBytes();

        $this->connection->method('fetchOne')->willReturn($connectionId);

        $mainResult = $this->createMock(Result::class);
        $mainResult->method('fetchAllAssociative')->willReturn([]);

        $levelResult = $this->createMock(Result::class);
        $levelResult->method('fetchAllAssociative')->willReturn([]);

        $capturedSql = '';

        $this->connection->method('executeQuery')
            ->willReturnCallback(static function (string $sql) use (&$capturedSql, $mainResult, $levelResult): Result {
                if ($capturedSql === '') {
                    $capturedSql = $sql;

                    return $mainResult;
                }

                return $levelResult;
            });

        $this->logGroupingService->getGroupedLogsByCodeAndEntity(
            Uuid::randomHex(),
            'error',
            1,
            10,
            $inputSortBy,
            'ASC',
            null,
            null,
            null,
            null
        );

        static::assertStringContainsString("ORDER BY {$expectedColumnInQuery} ASC", $capturedSql);
    }

    /**
     * @return iterable<string, array{inputSortBy: string, expectedColumnInQuery: string}>
     */
    public static function sortColumnValidationProvider(): iterable
    {
        yield 'valid sort by count' => [
            'inputSortBy' => 'count',
            'expectedColumnInQuery' => 'count',
        ];

        yield 'valid sort by code' => [
            'inputSortBy' => 'code',
            'expectedColumnInQuery' => 'code',
        ];

        yield 'valid sort by entityName' => [
            'inputSortBy' => 'entityName',
            'expectedColumnInQuery' => 'entity_name',
        ];

        yield 'valid sort by fieldName' => [
            'inputSortBy' => 'fieldName',
            'expectedColumnInQuery' => 'field_name',
        ];

        yield 'valid sort by profileName' => [
            'inputSortBy' => 'profileName',
            'expectedColumnInQuery' => 'profile_name',
        ];

        yield 'valid sort by gatewayName' => [
            'inputSortBy' => 'gatewayName',
            'expectedColumnInQuery' => 'gateway_name',
        ];

        yield 'invalid sort column falls back to count' => [
            'inputSortBy' => 'invalid_column',
            'expectedColumnInQuery' => 'count',
        ];

        yield 'SQL injection attempt falls back to count' => [
            'inputSortBy' => 'code; DROP TABLE users; --',
            'expectedColumnInQuery' => 'count',
        ];

        yield 'empty string falls back to count' => [
            'inputSortBy' => '',
            'expectedColumnInQuery' => 'count',
        ];
    }

    #[DataProvider('sortDirectionValidationProvider')]
    public function testSortDirectionValidation(
        string $inputSortDirection,
        string $expectedDirection,
    ): void {
        $connectionId = Uuid::randomBytes();

        $this->connection->method('fetchOne')->willReturn($connectionId);

        $mainResult = $this->createMock(Result::class);
        $mainResult->method('fetchAllAssociative')->willReturn([]);

        $levelResult = $this->createMock(Result::class);
        $levelResult->method('fetchAllAssociative')->willReturn([]);

        $capturedSql = '';

        $this->connection->method('executeQuery')
            ->willReturnCallback(static function (string $sql) use (&$capturedSql, $mainResult, $levelResult): Result {
                if ($capturedSql === '') {
                    $capturedSql = $sql;

                    return $mainResult;
                }

                return $levelResult;
            });

        $this->logGroupingService->getGroupedLogsByCodeAndEntity(
            Uuid::randomHex(),
            'error',
            1,
            10,
            'count',
            $inputSortDirection,
            null,
            null,
            null,
            null
        );

        static::assertStringContainsString("ORDER BY count {$expectedDirection}", $capturedSql);
    }

    /**
     * @return iterable<string, array{inputSortDirection: string, expectedDirection: string}>
     */
    public static function sortDirectionValidationProvider(): iterable
    {
        yield 'valid ASC uppercase' => [
            'inputSortDirection' => 'ASC',
            'expectedDirection' => 'ASC',
        ];

        yield 'valid DESC uppercase' => [
            'inputSortDirection' => 'DESC',
            'expectedDirection' => 'DESC',
        ];

        yield 'valid asc lowercase normalized to ASC' => [
            'inputSortDirection' => 'asc',
            'expectedDirection' => 'ASC',
        ];

        yield 'valid desc lowercase normalized to DESC' => [
            'inputSortDirection' => 'desc',
            'expectedDirection' => 'DESC',
        ];

        yield 'mixed case Asc normalized to ASC' => [
            'inputSortDirection' => 'Asc',
            'expectedDirection' => 'ASC',
        ];

        yield 'invalid direction falls back to DESC' => [
            'inputSortDirection' => 'invalid',
            'expectedDirection' => 'DESC',
        ];

        yield 'SQL injection attempt falls back to DESC' => [
            'inputSortDirection' => 'ASC; DROP TABLE users; --',
            'expectedDirection' => 'DESC',
        ];

        yield 'empty string falls back to DESC' => [
            'inputSortDirection' => '',
            'expectedDirection' => 'DESC',
        ];
    }

    #[DataProvider('filterStatusValidationProvider')]
    public function testFilterStatusValidation(
        ?string $inputFilterStatus,
        ?string $expectedMainWhereClause,
        bool $expectLevelJoinAndHavingClause,
        ?string $expectedHavingType,
        bool $expectPreviouslyFixedUnion,
    ): void {
        $connectionId = Uuid::randomBytes();

        $this->connection->method('fetchOne')->willReturn($connectionId);

        $mainResult = $this->createMock(Result::class);
        $mainResult->method('fetchAllAssociative')->willReturn([]);

        $levelResult = $this->createMock(Result::class);
        $levelResult->method('fetchAllAssociative')->willReturn([]);

        $capturedMainSql = '';
        $capturedLevelSql = '';

        $this->connection->method('executeQuery')
            ->willReturnCallback(static function (string $sql) use (&$capturedMainSql, &$capturedLevelSql, $mainResult, $levelResult): Result {
                if ($capturedMainSql === '') {
                    $capturedMainSql = $sql;

                    return $mainResult;
                }

                $capturedLevelSql = $sql;

                return $levelResult;
            });

        $this->logGroupingService->getGroupedLogsByCodeAndEntity(
            Uuid::randomHex(),
            'error',
            1,
            10,
            'count',
            'DESC',
            null,
            $inputFilterStatus,
            null,
            null
        );

        if ($expectedMainWhereClause !== null) {
            static::assertStringContainsString($expectedMainWhereClause, $capturedMainSql);
        } else {
            static::assertStringNotContainsString('WHERE `count` > 0 AND `count`', $capturedMainSql);
        }

        if ($expectLevelJoinAndHavingClause) {
            static::assertStringContainsString('LEFT JOIN swag_migration_fix f ON (', $capturedLevelSql);
            static::assertStringContainsString('HAVING COUNT(DISTINCT l.id)', $capturedLevelSql);

            if ($expectedHavingType === 'resolved') {
                static::assertStringContainsString('COUNT(DISTINCT l.id) = COUNT(DISTINCT f.id)', $capturedLevelSql);
            } elseif ($expectedHavingType === 'unresolved') {
                static::assertStringContainsString('COUNT(DISTINCT l.id) != COUNT(DISTINCT f.id)', $capturedLevelSql);
            }
        } else {
            static::assertStringNotContainsString('LEFT JOIN swag_migration_fix f ON (', $capturedLevelSql);
            static::assertStringNotContainsString('HAVING COUNT(DISTINCT l.id)', $capturedLevelSql);
        }

        static::assertStringContainsString('FROM swag_migration_fix f', $capturedMainSql);

        if ($expectPreviouslyFixedUnion) {
            static::assertStringContainsString('FROM swag_migration_fix f', $capturedLevelSql);
        } else {
            static::assertStringNotContainsString('FROM swag_migration_fix f', $capturedLevelSql);
        }
    }

    /**
     * @return iterable<string, array{inputFilterStatus: string|null, expectedMainWhereClause: string|null, expectLevelJoinAndHavingClause: bool, expectedHavingType: string|null, expectPreviouslyFixedUnion: bool}>
     */
    public static function filterStatusValidationProvider(): iterable
    {
        yield 'null filter status - no status filter clauses' => [
            'inputFilterStatus' => null,
            'expectedMainWhereClause' => null,
            'expectLevelJoinAndHavingClause' => false,
            'expectedHavingType' => null,
            'expectPreviouslyFixedUnion' => true,
        ];

        yield 'resolved filter status - outer where and level having with equals' => [
            'inputFilterStatus' => 'resolved',
            'expectedMainWhereClause' => 'WHERE `count` > 0 AND `count` = fix_count',
            'expectLevelJoinAndHavingClause' => true,
            'expectedHavingType' => 'resolved',
            'expectPreviouslyFixedUnion' => true,
        ];

        yield 'unresolved filter status - outer where and level having with not equals' => [
            'inputFilterStatus' => 'unresolved',
            'expectedMainWhereClause' => 'WHERE `count` > 0 AND `count` != fix_count',
            'expectLevelJoinAndHavingClause' => true,
            'expectedHavingType' => 'unresolved',
            'expectPreviouslyFixedUnion' => false,
        ];

        yield 'invalid filter status treated as null - no status filter clauses' => [
            'inputFilterStatus' => 'invalid_status',
            'expectedMainWhereClause' => null,
            'expectLevelJoinAndHavingClause' => false,
            'expectedHavingType' => null,
            'expectPreviouslyFixedUnion' => true,
        ];

        yield 'SQL injection attempt treated as null - no status filter clauses' => [
            'inputFilterStatus' => 'resolved\'; DROP TABLE users; --',
            'expectedMainWhereClause' => null,
            'expectLevelJoinAndHavingClause' => false,
            'expectedHavingType' => null,
            'expectPreviouslyFixedUnion' => true,
        ];

        yield 'empty string treated as null - no status filter clauses' => [
            'inputFilterStatus' => '',
            'expectedMainWhereClause' => null,
            'expectLevelJoinAndHavingClause' => false,
            'expectedHavingType' => null,
            'expectPreviouslyFixedUnion' => true,
        ];
    }

    #[DataProvider('filterParametersProvider')]
    public function testFilterParametersAddedToWhereClause(
        ?string $filterCode,
        ?string $filterEntity,
        ?string $filterField,
        bool $expectCodeCondition,
        bool $expectEntityCondition,
        bool $expectFieldCondition,
    ): void {
        $connectionId = Uuid::randomBytes();

        $this->connection->method('fetchOne')->willReturn($connectionId);

        $mainResult = $this->createMock(Result::class);
        $mainResult->method('fetchAllAssociative')->willReturn([]);

        $levelResult = $this->createMock(Result::class);
        $levelResult->method('fetchAllAssociative')->willReturn([]);

        $capturedMainSql = '';
        $capturedLevelSql = '';

        $this->connection->method('executeQuery')
            ->willReturnCallback(static function (string $sql) use (&$capturedMainSql, &$capturedLevelSql, $mainResult, $levelResult): Result {
                if ($capturedMainSql === '') {
                    $capturedMainSql = $sql;

                    return $mainResult;
                }

                $capturedLevelSql = $sql;

                return $levelResult;
            });

        $this->logGroupingService->getGroupedLogsByCodeAndEntity(
            Uuid::randomHex(),
            'error',
            1,
            10,
            'count',
            'DESC',
            $filterCode,
            null,
            $filterEntity,
            $filterField
        );

        if ($expectCodeCondition) {
            static::assertStringContainsString('l.code = :filterCode', $capturedMainSql);
            static::assertStringContainsString('l.code = :filterCode', $capturedLevelSql);
        } else {
            static::assertStringNotContainsString('l.code = :filterCode', $capturedMainSql);
            static::assertStringNotContainsString('l.code = :filterCode', $capturedLevelSql);
        }

        if ($expectEntityCondition) {
            static::assertStringContainsString('l.entity_name = :filterEntity', $capturedMainSql);
            static::assertStringContainsString('f.entity_name = :filterEntity', $capturedMainSql);
            static::assertStringContainsString('l.entity_name = :filterEntity', $capturedLevelSql);
            static::assertStringContainsString('f.entity_name = :filterEntity', $capturedLevelSql);
        } else {
            static::assertStringNotContainsString('l.entity_name = :filterEntity', $capturedMainSql);
            static::assertStringNotContainsString('f.entity_name = :filterEntity', $capturedMainSql);
            static::assertStringNotContainsString('l.entity_name = :filterEntity', $capturedLevelSql);
            static::assertStringNotContainsString('f.entity_name = :filterEntity', $capturedLevelSql);
        }

        if ($expectFieldCondition) {
            static::assertStringContainsString('l.field_name = :filterField', $capturedMainSql);
            static::assertStringContainsString('f.path = :filterField', $capturedMainSql);
            static::assertStringContainsString('l.field_name = :filterField', $capturedLevelSql);
            static::assertStringContainsString('f.path = :filterField', $capturedLevelSql);
        } else {
            static::assertStringNotContainsString('l.field_name = :filterField', $capturedMainSql);
            static::assertStringNotContainsString('f.path = :filterField', $capturedMainSql);
            static::assertStringNotContainsString('l.field_name = :filterField', $capturedLevelSql);
            static::assertStringNotContainsString('f.path = :filterField', $capturedLevelSql);
        }
    }

    /**
     * @return iterable<string, array{filterCode: string|null, filterEntity: string|null, filterField: string|null, expectCodeCondition: bool, expectEntityCondition: bool, expectFieldCondition: bool}>
     */
    public static function filterParametersProvider(): iterable
    {
        yield 'no filters' => [
            'filterCode' => null,
            'filterEntity' => null,
            'filterField' => null,
            'expectCodeCondition' => false,
            'expectEntityCondition' => false,
            'expectFieldCondition' => false,
        ];

        yield 'only code filter' => [
            'filterCode' => 'MISSING_FIELD',
            'filterEntity' => null,
            'filterField' => null,
            'expectCodeCondition' => true,
            'expectEntityCondition' => false,
            'expectFieldCondition' => false,
        ];

        yield 'only entity filter' => [
            'filterCode' => null,
            'filterEntity' => 'product',
            'filterField' => null,
            'expectCodeCondition' => false,
            'expectEntityCondition' => true,
            'expectFieldCondition' => false,
        ];

        yield 'only field filter' => [
            'filterCode' => null,
            'filterEntity' => null,
            'filterField' => 'name',
            'expectCodeCondition' => false,
            'expectEntityCondition' => false,
            'expectFieldCondition' => true,
        ];

        yield 'all filters' => [
            'filterCode' => 'MISSING_FIELD',
            'filterEntity' => 'product',
            'filterField' => 'name',
            'expectCodeCondition' => true,
            'expectEntityCondition' => true,
            'expectFieldCondition' => true,
        ];

        yield 'code and entity filters' => [
            'filterCode' => 'INVALID_FORMAT',
            'filterEntity' => 'customer',
            'filterField' => null,
            'expectCodeCondition' => true,
            'expectEntityCondition' => true,
            'expectFieldCondition' => false,
        ];
    }

    public function testGetUnresolvedLogsCountByCodeAndEntity(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn('1');

        $this->connection->method('executeQuery')->willReturn($result);

        $count = $this->logGroupingService->getUnresolvedLogsCountByCodeAndEntity(
            Uuid::randomHex(),
            'MISSING_FIELD',
            'product',
            'name',
            Uuid::randomHex(),
        );

        static::assertSame(1, $count);
    }

    public function testGetUnresolvedLogsCountByCodeAndEntityIncludesConnectionIdInSqlWhenItsPassed(): void
    {
        $connectionId = Uuid::randomHex();

        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn('1');

        $this->connection->method('executeQuery')
            ->willReturnCallback(static function (string $sql, array $params) use ($result, $connectionId) {
                static::assertStringContainsString(' AND f.connection_id = :connectionId', $sql);
                static::assertArrayHasKey('connectionId', $params);
                static::assertSame($connectionId, Uuid::fromBytesToHex($params['connectionId']));

                return $result;
            });

        $count = $this->logGroupingService->getUnresolvedLogsCountByCodeAndEntity(
            Uuid::randomHex(),
            'MISSING_FIELD',
            'product',
            'name',
            $connectionId,
        );

        static::assertSame(1, $count);
    }

    public function testGetUnresolvedLogsCountByCodeAndEntityNotIncludesConnectionIdInSqlWhenNullIsPassed(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn('1');

        $this->connection->method('executeQuery')
            ->willReturnCallback(static function (string $sql, array $params) use ($result) {
                static::assertStringNotContainsString(' AND f.connection_id = :connectionId', $sql);
                static::assertArrayNotHasKey('connectionId', $params);

                return $result;
            });

        $count = $this->logGroupingService->getUnresolvedLogsCountByCodeAndEntity(
            Uuid::randomHex(),
            'MISSING_FIELD',
            'product',
            'name'
        );

        static::assertSame(1, $count);
    }
}
