<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\History;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\AbstractMigrationLogEntry;

/**
 * @internal
 *
 * @SECURITY-NOTICE
 *
 * This service builds dynamic SQL queries with string interpolation for performance reasons.
 * While this looks dangerous, it is SAFE because:
 *
 * 1. All user-controllable values that get interpolated into SQL (sortBy, sortDirection, filterStatus)
 *    are validated against explicit allowlists BEFORE being used in query construction.
 *    See: ALLOWED_SORT_COLUMNS, ALLOWED_SORT_DIRECTIONS, ALLOWED_FILTER_STATUSES constants.
 *
 * 2. All data values (runId, connectionId, filterCode, filterEntity, filterField) are passed
 *    as bound parameters (:paramName), never interpolated into SQL strings.
 *
 * The validation is centralized at the top of getGroupedLogsByCodeAndEntity() to make
 * security review straightforward. Do not scatter validation logic across multiple methods.
 */
#[Package('fundamentals@after-sales')]
readonly class LogGroupingService
{
    /**
     * allowlist of columns that can be used for ORDER BY.
     * keys are API parameter names, values are actual SQL column references.
     *
     * @SECURITY only these exact values can be interpolated into ORDER BY clauses.
     */
    private const ALLOWED_SORT_COLUMNS = [
        'count' => 'count',
        'code' => 'l.code',
        'entityName' => 'l.entity_name',
        'fieldName' => 'l.field_name',
        'profileName' => 'l.profile_name',
        'gatewayName' => 'l.gateway_name',
    ];

    /**
     * allowlist of valid sort directions.
     *
     * @SECURITY only these exact values can be interpolated into ORDER BY clauses.
     */
    private const ALLOWED_SORT_DIRECTIONS = ['ASC', 'DESC'];

    /**
     * allowlist of valid filter status values.
     *
     * @SECURITY only these exact values can trigger HAVING clause generation.
     */
    private const ALLOWED_FILTER_STATUSES = ['resolved', 'unresolved'];

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @throws Exception
     *
     * @return array{total: int, items: array<int, array{code: string, entityName: string|null, fieldName: string|null, count: int, fixCount: int}>, levelCounts: array{error: int, warning: int, info: int}}
     */
    public function getGroupedLogsByCodeAndEntity(
        string $runUuid,
        string $level,
        int $page,
        int $limit,
        string $sortBy,
        string $sortDirection,
        ?string $filterCode,
        ?string $filterStatus,
        ?string $filterEntity,
        ?string $filterField,
    ): array {
        /** @SECURITY validates all values that will be interpolated into SQL - do not skip this step. */
        $orderColumn = $this->validateSortColumn($sortBy);
        $orderDirection = $this->validateSortDirection($sortDirection);
        $validatedFilterStatus = $this->validateFilterStatus($filterStatus);

        $runIdBytes = Uuid::fromHexToBytes($runUuid);
        $connectionIdBytes = $this->getConnectionIdForRun($runIdBytes);

        $params = [
            'runId' => $runIdBytes,
            'level' => $level,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
            'connectionId' => $connectionIdBytes,
        ];

        // build optional WHERE conditions
        $whereConditions = [];

        if ($filterCode !== null) {
            $whereConditions[] = 'l.code = :filterCode';
            $params['filterCode'] = $filterCode;
        }

        if ($filterEntity !== null) {
            $whereConditions[] = 'l.entity_name = :filterEntity';
            $params['filterEntity'] = $filterEntity;
        }

        if ($filterField !== null) {
            $whereConditions[] = 'l.field_name = :filterField';
            $params['filterField'] = $filterField;
        }

        $additionalWhere = $whereConditions !== [] ? ' AND ' . \implode(' AND ', $whereConditions) : '';

        // determine if we need the fix join for status filtering
        $includeFixJoin = $validatedFilterStatus !== null;

        $sql = $this->buildMainQuery(
            $additionalWhere,
            $validatedFilterStatus,
            $orderColumn,
            $orderDirection,
            $includeFixJoin
        );

        $result = $this->connection->executeQuery($sql, $params, [
            'limit' => ParameterType::INTEGER,
            'offset' => ParameterType::INTEGER,
        ]);

        $rows = $result->fetchAllAssociative();
        $total = $rows !== [] ? (int) $rows[0]['total'] : 0;

        $levelCounts = $this->getLogLevelCounts(
            $params,
            $additionalWhere,
            $validatedFilterStatus,
            $includeFixJoin
        );

        return [
            'total' => $total,
            'items' => $this->mapLogsFromRows($rows),
            'levelCounts' => $levelCounts,
        ];
    }

    /**
     * @throws Exception
     */
    public function getUnresolvedLogsCountByCodeAndEntity(
        string $runId,
        string $code,
        string $entityName,
        string $fieldName,
        ?string $connectionId = null,
    ): int {
        $params = [
            'runId' => Uuid::fromHexToBytes($runId),
            'code' => $code,
            'entityName' => $entityName,
            'fieldName' => $fieldName,
        ];

        // this is safe, it's a static string, not user input
        $connectionJoinCondition = '';

        if ($connectionId !== null && $connectionId !== '') {
            $connectionJoinCondition = ' AND f.connection_id = :connectionId';
            $params['connectionId'] = Uuid::fromHexToBytes($connectionId);
        }

        $sql = "
            SELECT COUNT(*) as count
            FROM swag_migration_logging l
            LEFT JOIN swag_migration_fix f ON (
                f.entity_name = l.entity_name
                AND f.path = l.field_name
                AND f.entity_id = l.entity_id
                {$connectionJoinCondition}
            )
            WHERE l.run_id = :runId
                AND l.code = :code
                AND l.entity_name = :entityName
                AND l.field_name = :fieldName
                AND l.user_fixable = 1
                AND f.id IS NULL
        ";

        $result = $this->connection->executeQuery($sql, $params);

        return (int) $result->fetchOne();
    }

    /**
     * @throws Exception
     *
     * @return array<string>
     */
    public function getLogEntityIdsWithoutFixByCodeAndEntity(
        string $runId,
        string $code,
        string $entityName,
        string $fieldName,
        int $limit,
        ?string $connectionId = null,
    ): array {
        $params = [
            'runId' => Uuid::fromHexToBytes($runId),
            'code' => $code,
            'entityName' => $entityName,
            'fieldName' => $fieldName,
            'limit' => $limit,
        ];

        $types = [
            'runId' => ParameterType::BINARY,
            'code' => ParameterType::STRING,
            'entityName' => ParameterType::STRING,
            'fieldName' => ParameterType::STRING,
            'limit' => ParameterType::INTEGER,
        ];

        // this is safe, it's a static string, not user input
        $connectionJoinCondition = '';

        if ($connectionId !== null && $connectionId !== '') {
            $connectionJoinCondition = ' AND f.connection_id = :connectionId';
            $params['connectionId'] = Uuid::fromHexToBytes($connectionId);
        }

        $sql = "
            SELECT LOWER(HEX(l.entity_id)) as entity_id
            FROM swag_migration_logging l
            LEFT JOIN swag_migration_fix f ON (
                f.entity_name = l.entity_name
                AND f.path = l.field_name
                AND f.entity_id = l.entity_id
                {$connectionJoinCondition}
            )
            WHERE l.run_id = :runId
                AND l.code = :code
                AND l.entity_name = :entityName
                AND l.field_name = :fieldName
                AND l.user_fixable = 1
                AND f.id IS NULL
            ORDER BY l.auto_increment ASC
            LIMIT :limit
        ";

        $result = $this->connection->executeQuery($sql, $params, $types);

        return \array_column($result->fetchAllAssociative(), 'entity_id');
    }

    /**
     * validates sortBy parameter against allowlist and returns the SQL column name.
     * returns 'count' as default if input is not in ALLOWED_SORT_COLUMNS.
     */
    private function validateSortColumn(string $sortBy): string
    {
        return self::ALLOWED_SORT_COLUMNS[$sortBy] ?? 'count';
    }

    /**
     * validates sortDirection parameter against allowlist.
     * returns 'DESC' as default if input is not in ALLOWED_SORT_DIRECTIONS.
     */
    private function validateSortDirection(string $sortDirection): string
    {
        $normalized = \strtoupper($sortDirection);

        return \in_array($normalized, self::ALLOWED_SORT_DIRECTIONS, true) ? $normalized : 'DESC';
    }

    /**
     * validates filterStatus parameter against allowlist.
     * returns null if input is not in ALLOWED_FILTER_STATUSES.
     */
    private function validateFilterStatus(?string $filterStatus): ?string
    {
        if ($filterStatus === null) {
            return null;
        }

        return \in_array($filterStatus, self::ALLOWED_FILTER_STATUSES, true) ? $filterStatus : null;
    }

    /**
     * builds the main grouped logs query.
     */
    private function buildMainQuery(
        string $additionalWhere,
        ?string $filterStatus,
        string $orderColumn,
        string $orderDirection,
        bool $includeFixJoin,
    ): string {
        // build HAVING clause from validated filter status
        $havingClause = match ($filterStatus) {
            'resolved' => 'HAVING COUNT(DISTINCT l.id) > 0 AND COUNT(DISTINCT l.id) = COUNT(DISTINCT f.id)',
            'unresolved' => 'HAVING COUNT(DISTINCT l.id) > 0 AND COUNT(DISTINCT l.id) != COUNT(DISTINCT f.id)',
            default => '',
        };

        // build count subquery components
        $countSubqueryFixJoin = $includeFixJoin
            ? 'LEFT JOIN swag_migration_fix f2 ON (
                        f2.connection_id = :connectionId
                        AND f2.entity_name = l2.entity_name
                        AND f2.path = l2.field_name
                        AND f2.entity_id = l2.entity_id
                    )'
            : '';

        $countSubqueryHaving = match ($filterStatus) {
            'resolved' => 'HAVING COUNT(DISTINCT l2.id) > 0 AND COUNT(DISTINCT l2.id) = COUNT(DISTINCT f2.id)',
            'unresolved' => 'HAVING COUNT(DISTINCT l2.id) > 0 AND COUNT(DISTINCT l2.id) != COUNT(DISTINCT f2.id)',
            default => '',
        };

        $countSubqueryWhere = \str_replace('l.', 'l2.', $additionalWhere);

        /*
         * MAIN QUERY STRUCTURE:
         * this query groups logs by code, entity_name, and field_name, counting occurrences
         * and tracking fix status. The subquery calculates total count for pagination.
         */
        return "
            SELECT
                l.code,
                l.entity_name,
                l.field_name,
                l.gateway_name,
                l.profile_name,
                COUNT(DISTINCT l.id) as count,
                (
                    SELECT COUNT(*)
                    FROM (
                        SELECT 1
                        FROM swag_migration_logging l2
                        {$countSubqueryFixJoin}
                        WHERE l2.run_id = :runId
                            AND l2.level = :level
                            AND l2.user_fixable = 1
                            {$countSubqueryWhere}
                        GROUP BY l2.code, l2.entity_name, l2.field_name
                        {$countSubqueryHaving}
                    ) as grouped_logs
                ) as total,
                COUNT(DISTINCT f.id) as fix_count
            FROM swag_migration_logging l
            LEFT JOIN swag_migration_fix f ON (
                f.connection_id = :connectionId
                AND f.entity_name = l.entity_name
                AND f.path = l.field_name
                AND f.entity_id = l.entity_id
            )
            WHERE l.run_id = :runId
                AND l.level = :level
                AND l.user_fixable = 1
                {$additionalWhere}
            GROUP BY l.code, l.entity_name, l.field_name
            {$havingClause}
            ORDER BY {$orderColumn} {$orderDirection}, l.code ASC, l.entity_name ASC, l.field_name ASC
            LIMIT :limit OFFSET :offset
        ";
    }

    /**
     * gets log counts grouped by level for the filter badge counts.
     *
     * @param array<string, mixed> $params
     *
     * @throws Exception
     *
     * @return array{error: int, warning: int, info: int}
     */
    private function getLogLevelCounts(
        array $params,
        string $additionalWhere,
        ?string $filterStatus,
        bool $includeFixJoin,
    ): array {
        $joinClause = $includeFixJoin
            ? 'LEFT JOIN swag_migration_fix f ON (
                    f.connection_id = :connectionId
                    AND f.entity_name = l.entity_name
                    AND f.path = l.field_name
                    AND f.entity_id = l.entity_id
                )'
            : '';

        $havingClause = $includeFixJoin
            ? match ($filterStatus) {
                'resolved' => 'HAVING COUNT(DISTINCT l.id) > 0 AND COUNT(DISTINCT l.id) = COUNT(DISTINCT f.id)',
                'unresolved' => 'HAVING COUNT(DISTINCT l.id) > 0 AND COUNT(DISTINCT l.id) != COUNT(DISTINCT f.id)',
                default => '',
            }
        : '';

        $sql = "
            SELECT
                level,
                COUNT(DISTINCT CONCAT(code, '|', COALESCE(entity_name, ''), '|', COALESCE(field_name, ''))) as count
            FROM (
                SELECT
                    l.level,
                    l.code,
                    l.entity_name,
                    l.field_name
                FROM swag_migration_logging l
                {$joinClause}
                WHERE l.run_id = :runId
                    AND l.user_fixable = 1
                    {$additionalWhere}
                GROUP BY l.level, l.code, l.entity_name, l.field_name
                {$havingClause}
            ) as filtered_logs
            GROUP BY level
        ";

        $result = $this->connection->executeQuery($sql, $params);

        return $this->mapLevelCountsFromRows($result->fetchAllAssociative());
    }

    /**
     * @throws Exception
     */
    private function getConnectionIdForRun(string $runIdBytes): string
    {
        $result = $this->connection->fetchOne(
            'SELECT connection_id FROM swag_migration_run WHERE id = :runId',
            ['runId' => $runIdBytes]
        );

        if ($result === false) {
            throw MigrationException::noConnectionFound();
        }

        return $result;
    }

    /**
     * @param array<array<string, mixed>> $rows
     *
     * @return array<int, array{code: string, entityName: string|null, fieldName: string|null, profileName: string, gatewayName: string, count: int, fixCount: int}>
     */
    private function mapLogsFromRows(array $rows): array
    {
        return \array_map(
            static fn (array $row) => [
                'code' => $row['code'],
                'entityName' => $row['entity_name'],
                'fieldName' => $row['field_name'],
                'profileName' => $row['profile_name'],
                'gatewayName' => $row['gateway_name'],
                'count' => (int) $row['count'],
                'fixCount' => (int) $row['fix_count'],
            ],
            $rows
        );
    }

    /**
     * @param array<array<string, mixed>> $rows
     *
     * @return array{error: int, warning: int, info: int}
     */
    private function mapLevelCountsFromRows(array $rows): array
    {
        $counts = [
            AbstractMigrationLogEntry::LOG_LEVEL_ERROR => 0,
            AbstractMigrationLogEntry::LOG_LEVEL_WARNING => 0,
            AbstractMigrationLogEntry::LOG_LEVEL_INFO => 0,
        ];

        foreach ($rows as $row) {
            $level = \strtolower($row['level']);

            if (isset($counts[$level])) {
                $counts[$level] = (int) $row['count'];
            }
        }

        return $counts;
    }
}
