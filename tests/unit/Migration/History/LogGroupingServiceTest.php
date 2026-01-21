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
     * @param array<int, array{code: string, entityName: string|null, fieldName: string|null, profileName: string, gatewayName: string, count: int, fixCount: int}> $expectedItems
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
     * @return iterable<string, array{dbRows: array<int, array<string, mixed>>, levelCountRows: array<int, array<string, mixed>>, expectedTotal: int, expectedItems: array<int, array{code: string, entityName: string|null, fieldName: string|null, profileName: string, gatewayName: string, count: int, fixCount: int}>, expectedLevelCounts: array{error: int, warning: int, info: int}}>
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
                ],
            ],
            'expectedLevelCounts' => ['error' => 1, 'warning' => 0, 'info' => 0],
        ];

        yield 'multiple log entries with all levels' => [
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
                ],
                [
                    'code' => 'INVALID_FORMAT',
                    'entityName' => 'customer',
                    'fieldName' => 'email',
                    'profileName' => 'shopware55',
                    'gatewayName' => 'api',
                    'count' => 3,
                    'fixCount' => 0,
                ],
                [
                    'code' => 'DEPRECATED_FIELD',
                    'entityName' => null,
                    'fieldName' => null,
                    'profileName' => 'shopware6',
                    'gatewayName' => 'local',
                    'count' => 1,
                    'fixCount' => 1,
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
                ],
            ],
            'expectedLevelCounts' => ['error' => 3, 'warning' => 2, 'info' => 0],
        ];
    }

    /**
     * @param array<int, array<string, string>> $dbResult
     * @param array<string> $expectedEntityIds
     */
    #[DataProvider('getAllLogIdsDataProvider')]
    public function testGetAllLogEntityIdsByCodeAndEntity(
        array $dbResult,
        ?string $connectionId,
        array $expectedEntityIds,
    ): void {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($dbResult);

        $this->connection->method('executeQuery')->willReturn($result);

        $ids = $this->logGroupingService->getAllLogEntityIdsByCodeAndEntity(
            Uuid::randomHex(),
            'MISSING_FIELD',
            'product',
            'name',
            $connectionId
        );

        static::assertSame($expectedEntityIds, $ids);
    }

    /**
     * @return iterable<string, array{dbResult: array<int, array<string, string>>, connectionId: string|null, expectedEntityIds: array<string>}>
     */
    public static function getAllLogIdsDataProvider(): iterable
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
            ->willReturnCallback(function (string $sql) use (&$capturedSql, $mainResult, $levelResult): Result {
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
            'expectedColumnInQuery' => 'l.code',
        ];

        yield 'valid sort by entityName' => [
            'inputSortBy' => 'entityName',
            'expectedColumnInQuery' => 'l.entity_name',
        ];

        yield 'valid sort by fieldName' => [
            'inputSortBy' => 'fieldName',
            'expectedColumnInQuery' => 'l.field_name',
        ];

        yield 'valid sort by profileName' => [
            'inputSortBy' => 'profileName',
            'expectedColumnInQuery' => 'l.profile_name',
        ];

        yield 'valid sort by gatewayName' => [
            'inputSortBy' => 'gatewayName',
            'expectedColumnInQuery' => 'l.gateway_name',
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
            ->willReturnCallback(function (string $sql) use (&$capturedSql, $mainResult, $levelResult): Result {
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
        bool $expectHavingClause,
        ?string $expectedHavingType,
    ): void {
        $connectionId = Uuid::randomBytes();

        $this->connection->method('fetchOne')->willReturn($connectionId);

        $mainResult = $this->createMock(Result::class);
        $mainResult->method('fetchAllAssociative')->willReturn([]);

        $levelResult = $this->createMock(Result::class);
        $levelResult->method('fetchAllAssociative')->willReturn([]);

        $capturedSql = '';
        $this->connection->method('executeQuery')
            ->willReturnCallback(function (string $sql) use (&$capturedSql, $mainResult, $levelResult): Result {
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
            'DESC',
            null,
            $inputFilterStatus,
            null,
            null
        );

        if ($expectHavingClause) {
            static::assertStringContainsString('HAVING COUNT(DISTINCT l.id)', $capturedSql);

            if ($expectedHavingType === 'resolved') {
                static::assertStringContainsString('COUNT(DISTINCT l.id) = COUNT(DISTINCT f.id)', $capturedSql);
            } elseif ($expectedHavingType === 'unresolved') {
                static::assertStringContainsString('COUNT(DISTINCT l.id) != COUNT(DISTINCT f.id)', $capturedSql);
            }
        } else {
            static::assertStringNotContainsString('HAVING COUNT(DISTINCT l.id)', $capturedSql);
        }
    }

    /**
     * @return iterable<string, array{inputFilterStatus: string|null, expectHavingClause: bool, expectedHavingType: string|null}>
     */
    public static function filterStatusValidationProvider(): iterable
    {
        yield 'null filter status - no HAVING clause' => [
            'inputFilterStatus' => null,
            'expectHavingClause' => false,
            'expectedHavingType' => null,
        ];

        yield 'resolved filter status - HAVING with equals' => [
            'inputFilterStatus' => 'resolved',
            'expectHavingClause' => true,
            'expectedHavingType' => 'resolved',
        ];

        yield 'unresolved filter status - HAVING with not equals' => [
            'inputFilterStatus' => 'unresolved',
            'expectHavingClause' => true,
            'expectedHavingType' => 'unresolved',
        ];

        yield 'invalid filter status treated as null - no HAVING clause' => [
            'inputFilterStatus' => 'invalid_status',
            'expectHavingClause' => false,
            'expectedHavingType' => null,
        ];

        yield 'SQL injection attempt treated as null - no HAVING clause' => [
            'inputFilterStatus' => 'resolved\'; DROP TABLE users; --',
            'expectHavingClause' => false,
            'expectedHavingType' => null,
        ];

        yield 'empty string treated as null - no HAVING clause' => [
            'inputFilterStatus' => '',
            'expectHavingClause' => false,
            'expectedHavingType' => null,
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

        $capturedSql = '';
        $this->connection->method('executeQuery')
            ->willReturnCallback(function (string $sql) use (&$capturedSql, $mainResult, $levelResult): Result {
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
            'DESC',
            $filterCode,
            null,
            $filterEntity,
            $filterField
        );

        if ($expectCodeCondition) {
            static::assertStringContainsString('l.code = :filterCode', $capturedSql);
        } else {
            static::assertStringNotContainsString('l.code = :filterCode', $capturedSql);
        }

        if ($expectEntityCondition) {
            static::assertStringContainsString('l.entity_name = :filterEntity', $capturedSql);
        } else {
            static::assertStringNotContainsString('l.entity_name = :filterEntity', $capturedSql);
        }

        if ($expectFieldCondition) {
            static::assertStringContainsString('l.field_name = :filterField', $capturedSql);
        } else {
            static::assertStringNotContainsString('l.field_name = :filterField', $capturedSql);
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
}
