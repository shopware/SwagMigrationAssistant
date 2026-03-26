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
     * @return array{total: int, items: array<int, array{code: string, entityName: string|null, fieldName: string|null, profileName: string, gatewayName: string, count: int, fixCount: int, isPreviouslyFixed: bool}>, levelCounts: array{error: int, warning: int, info: int}}
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

        // for the previously fixed part of the UNION, entity/field conditions reference different columns
        $additionalWherePreviouslyFixed = \str_replace(
            ['l.entity_name', 'l.field_name'],
            ['f.entity_name', 'f.path'],
            $additionalWhere
        );

        $includeFixJoin = $validatedFilterStatus !== null;

        $sql = $this->buildMainQuery(
            $additionalWhere,
            $additionalWherePreviouslyFixed,
            $validatedFilterStatus,
            $orderColumn,
            $orderDirection,
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
            $additionalWherePreviouslyFixed,
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
     * builds the unified grouped-logs query.
     *
     * Uses UNION ALL to combine current-run log groups with previously fixed groups
     * (fixes that exist for this connection but have no log in the current run).
     * A window function replaces the old count subquery for simpler pagination totals.
     *
     * Filter status is applied as an outer WHERE rather than HAVING on each branch,
     * which naturally excludes previously fixed groups when filtering for 'unresolved'
     * (they always have count = fix_count) and includes them for 'resolved'.
     */
    private function buildMainQuery(
        string $additionalWhere,
        string $additionalWherePreviouslyFixed,
        ?string $filterStatus,
        string $orderColumn,
        string $orderDirection,
    ): string {
        /*
         * @SECURITY $orderColumn is validated against ALLOWED_SORT_COLUMNS before reaching here.
         * The outer ORDER BY uses unqualified aliases, so strip the 'l.' table qualifier.
         */
        $outerOrderColumn = \str_replace('l.', '', $orderColumn);

        $outerWhereClause = match ($filterStatus) {
            'resolved' => 'WHERE `count` > 0 AND `count` = fix_count',
            'unresolved' => 'WHERE `count` > 0 AND `count` != fix_count',
            default => '',
        };

        return "
            SELECT
                code,
                entity_name,
                field_name,
                gateway_name,
                profile_name,
                count,
                fix_count,
                is_previously_fixed,
                COUNT(*) OVER() AS total
            FROM (
                SELECT
                    l.code,
                    l.entity_name,
                    l.field_name,
                    l.gateway_name,
                    l.profile_name,
                    COUNT(DISTINCT l.id)  AS count,
                    COUNT(DISTINCT f.id)  AS fix_count,
                    0                     AS is_previously_fixed
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

                UNION ALL

                SELECT
                    l.code,
                    f.entity_name,
                    f.path                      AS field_name,
                    MIN(l.gateway_name)         AS gateway_name,
                    MIN(l.profile_name)         AS profile_name,
                    COUNT(DISTINCT f.entity_id) AS count,
                    COUNT(DISTINCT f.entity_id) AS fix_count,
                    1                           AS is_previously_fixed
                FROM swag_migration_fix f
                JOIN swag_migration_logging l ON (
                    l.entity_id   = f.entity_id
                    AND l.entity_name = f.entity_name
                    AND l.field_name  = f.path
                    AND l.user_fixable = 1
                    AND l.level       = :level
                )
                JOIN swag_migration_run r ON (
                    l.run_id        = r.id
                    AND r.connection_id = :connectionId
                )
                WHERE f.connection_id = :connectionId
                    AND NOT EXISTS (
                        SELECT 1
                        FROM swag_migration_logging curr
                        WHERE curr.run_id       = :runId
                          AND curr.entity_id   = f.entity_id
                          AND curr.entity_name = f.entity_name
                          AND curr.field_name  = f.path
                          AND curr.user_fixable = 1
                    )
                    {$additionalWherePreviouslyFixed}
                GROUP BY l.code, f.entity_name, f.path
            ) AS unified
            {$outerWhereClause}
            ORDER BY {$outerOrderColumn} {$orderDirection}, code ASC, entity_name ASC, field_name ASC
            LIMIT :limit OFFSET :offset
        ";
    }

    /**
     * gets log counts grouped by level for the filter badge counts.
     *
     * Uses the same UNION ALL structure as buildMainQuery so that previously fixed
     * groups are reflected in the badge counts alongside current-run groups.
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
        string $additionalWherePreviouslyFixed,
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

        $previouslyFixedUnion = $filterStatus !== 'unresolved'
            ? "
                UNION ALL

                SELECT
                    l.level,
                    l.code,
                    f.entity_name,
                    f.path AS field_name
                FROM swag_migration_fix f
                JOIN swag_migration_logging l ON (
                    l.entity_id   = f.entity_id
                    AND l.entity_name = f.entity_name
                    AND l.field_name  = f.path
                    AND l.user_fixable = 1
                )
                JOIN swag_migration_run r ON (
                    l.run_id        = r.id
                    AND r.connection_id = :connectionId
                )
                WHERE f.connection_id = :connectionId
                    AND NOT EXISTS (
                        SELECT 1
                        FROM swag_migration_logging curr
                        WHERE curr.run_id       = :runId
                          AND curr.entity_id   = f.entity_id
                          AND curr.entity_name = f.entity_name
                          AND curr.field_name  = f.path
                          AND curr.user_fixable = 1
                    )
                    {$additionalWherePreviouslyFixed}
                GROUP BY l.level, l.code, f.entity_name, f.path
            "
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
                {$previouslyFixedUnion}
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
     * @return array<int, array{code: string, entityName: string|null, fieldName: string|null, profileName: string, gatewayName: string, count: int, fixCount: int, isPreviouslyFixed: bool}>
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
                'isPreviouslyFixed' => (bool) $row['is_previously_fixed'],
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
