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
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

#[Package('fundamentals@after-sales')]
class LogGroupingService implements LogGroupingServiceInterface
{
    /**
     * @param EntityRepository<SwagMigrationRunCollection> $runRepo
     */
    public function __construct(
        private readonly EntityRepository $runRepo,
        private readonly Connection $connection,
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
        Context $context,
    ): array {
        $run = $this->getMigrationRunForLogs($runUuid, $context);
        $connectionIdBytes = Uuid::fromHexToBytes($run->getConnectionId() ?? '');
        $runIdBytes = Uuid::fromHexToBytes($runUuid);

        $params = $this->buildParams(
            $runIdBytes,
            $level,
            $page,
            $limit,
            $connectionIdBytes,
            $filterCode,
            $filterEntity,
            $filterField
        );

        $whereClause = $this->buildWhereClause($filterCode, $filterEntity, $filterField);
        $havingClause = $this->buildHavingClause($filterStatus);
        $orderByClause = $this->buildOrderByClause($sortBy, $sortDirection);

        $sql = $this->buildQuery(
            $whereClause,
            $havingClause,
            $orderByClause,
            $filterStatus !== null
        );

        $result = $this->connection->executeQuery($sql, $params, [
            'limit' => ParameterType::INTEGER,
            'offset' => ParameterType::INTEGER,
        ]);

        $rows = $result->fetchAllAssociative();
        $total = \count($rows) > 0 ? (int) $rows[0]['total'] : 0;

        $levelCounts = $this->getLogLevelCounts(
            $params,
            $whereClause,
            $havingClause,
            $filterStatus !== null
        );

        return [
            'total' => $total,
            'items' => $this->mapLogsFromRows($rows),
            'levelCounts' => $levelCounts,
        ];
    }

    /**
     * @throws Exception
     *
     * @return array<string>
     */
    public function getAllLogIdsByCodeAndEntity(
        string $code,
        string $entityName,
        string $fieldName,
        ?string $connectionId = null,
    ): array {
        $join = '
            LEFT JOIN swag_migration_fix f ON (
                f.entity_name = l.entity_name
                AND f.path = l.field_name
                AND f.entity_id = l.entity_id
        ';

        $params = [
            'code' => $code,
            'entityName' => $entityName,
            'fieldName' => $fieldName,
        ];

        if ($connectionId !== null && $connectionId !== '') {
            $join .= ' AND f.connection_id = :connectionId';
            $params['connectionId'] = Uuid::fromHexToBytes($connectionId);
        }

        $join .= ')';

        $sql = "
            SELECT LOWER(HEX(l.id)) as id
            FROM swag_migration_logging l
            {$join}
            WHERE l.code = :code
                AND l.entity_name = :entityName
                AND l.field_name = :fieldName
                AND l.user_fixable = 1
                AND f.id IS NULL
        ";

        $result = $this->connection->executeQuery($sql, $params);

        $rows = $result->fetchAllAssociative();

        return \array_column($rows, 'id');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildParams(
        string $runIdBytes,
        string $level,
        int $page,
        int $limit,
        string $connectionIdBytes,
        ?string $filterCode,
        ?string $filterEntity,
        ?string $filterField,
    ): array {
        $params = [
            'runId' => $runIdBytes,
            'level' => $level,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
            'connectionId' => $connectionIdBytes,
        ];

        if ($filterCode !== null) {
            $params['filterCode'] = $filterCode;
        }

        if ($filterEntity !== null) {
            $params['filterEntity'] = $filterEntity;
        }

        if ($filterField !== null) {
            $params['filterField'] = $filterField;
        }

        return $params;
    }

    private function buildWhereClause(?string $filterCode, ?string $filterEntity, ?string $filterField): string
    {
        $conditions = [];

        if ($filterCode !== null) {
            $conditions[] = 'l.code = :filterCode';
        }

        if ($filterEntity !== null) {
            $conditions[] = 'l.entity_name = :filterEntity';
        }

        if ($filterField !== null) {
            $conditions[] = 'l.field_name = :filterField';
        }

        return $conditions ? ' AND ' . \implode(' AND ', $conditions) : '';
    }

    private function buildQuery(string $whereClause, string $havingClause, string $orderByClause, bool $includeFixJoin, ?string $filterStatus = null): string
    {
        $fixJoin = $this->getFixJoinClause();
        $countSubquery = $this->buildCountSubquery(
            $whereClause,
            $filterStatus,
            $includeFixJoin,
        );

        return "
            SELECT
                l.code,
                l.entity_name,
                l.field_name,
                l.gateway_name,
                l.profile_name,
                COUNT(DISTINCT l.id) as count,
                {$countSubquery} as total,
                COUNT(DISTINCT f.id) as fix_count
            FROM swag_migration_logging l
            {$fixJoin}
            WHERE l.run_id = :runId
                AND l.level = :level
                AND l.user_fixable = 1
                {$whereClause}
            GROUP BY l.code, l.entity_name, l.field_name
            {$havingClause}
            {$orderByClause}
            LIMIT :limit OFFSET :offset
        ";
    }

    private function buildCountSubquery(string $whereClause, ?string $filterStatus, bool $includeFixJoin): string
    {
        if ($includeFixJoin) {
            $fixJoin = $this->getFixJoinClause('l2', 'f2');
            $subqueryHaving = $this->buildHavingClause($filterStatus, 'l2', 'f2');
        } else {
            $fixJoin = '';
            $subqueryHaving = '';
        }

        $subqueryWhere = \str_replace('l.', 'l2.', $whereClause);

        return "(
                SELECT COUNT(*)
                FROM (
                    SELECT 1
                    FROM swag_migration_logging l2
                    {$fixJoin}
                    WHERE l2.run_id = :runId
                        AND l2.level = :level
                        AND l2.user_fixable = 1
                        {$subqueryWhere}
                    GROUP BY l2.code, l2.entity_name, l2.field_name
                    {$subqueryHaving}
                ) as grouped_logs
            )";
    }

    private function getFixJoinClause(string $loggingAlias = 'l', string $fixAlias = 'f'): string
    {
        return 'LEFT JOIN swag_migration_fix ' . $fixAlias . ' ON (
                ' . $fixAlias . '.connection_id = :connectionId
                AND ' . $fixAlias . '.entity_name = ' . $loggingAlias . '.entity_name
                AND ' . $fixAlias . '.path = ' . $loggingAlias . '.field_name
                AND ' . $fixAlias . '.entity_id = ' . $loggingAlias . '.entity_id
            )';
    }

    private function buildHavingClause(?string $filterStatus, string $loggingAlias = 'l', string $fixAlias = 'f'): string
    {
        if ($filterStatus === 'resolved') {
            return ' HAVING COUNT(DISTINCT ' . $loggingAlias . '.id) > 0 AND COUNT(DISTINCT ' . $loggingAlias . '.id) = COUNT(DISTINCT ' . $fixAlias . '.id)';
        }

        if ($filterStatus === 'unresolved') {
            return ' HAVING COUNT(DISTINCT ' . $loggingAlias . '.id) > 0 AND COUNT(DISTINCT ' . $loggingAlias . '.id) != COUNT(DISTINCT ' . $fixAlias . '.id)';
        }

        return '';
    }

    private function buildOrderByClause(string $sortBy, string $sortDirection): string
    {
        $columnMap = [
            'count' => 'count',
            'code' => 'l.code',
            'entityName' => 'l.entity_name',
            'fieldName' => 'l.field_name',
            'profileName' => 'l.profile_name',
            'gatewayName' => 'l.gateway_name',
            'createdAt' => 'l.code',
        ];

        $orderColumn = $columnMap[$sortBy] ?? 'count';
        $direction = \in_array(\strtoupper($sortDirection), ['ASC', 'DESC'], true) ?
            \strtoupper($sortDirection) : 'DESC';

        return "ORDER BY {$orderColumn} {$direction}, l.code ASC, l.entity_name ASC, l.field_name ASC";
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

    private function getMigrationRunForLogs(string $runUuid, Context $context): SwagMigrationRunEntity
    {
        $runCriteria = new Criteria();
        $runCriteria->addFilter(new EqualsFilter('id', $runUuid));

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->runRepo->search($runCriteria, $context)->first();

        if ($run === null) {
            throw MigrationException::entityNotExists(SwagMigrationRunEntity::class, $runUuid);
        }

        if ($run->getConnectionId() === null) {
            throw MigrationException::noConnectionFound();
        }

        return $run;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws Exception
     *
     * @return array{error: int, warning: int, info: int}
     */
    private function getLogLevelCounts(
        array $params,
        string $whereClause,
        string $havingClause,
        bool $includeFixJoin,
    ): array {
        $groupByClause = ', l.code, l.entity_name, l.field_name';

        if ($includeFixJoin) {
            $joinClause = $this->getFixJoinClause();
            $havingClauseForQuery = $havingClause;
        } else {
            $joinClause = '';
            $havingClauseForQuery = '';
        }

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
                    {$whereClause}
                GROUP BY l.level{$groupByClause}
                {$havingClauseForQuery}
            ) as filtered_logs
            GROUP BY level
        ";

        $result = $this->connection->executeQuery($sql, $params);
        $rows = $result->fetchAllAssociative();

        return $this->mapLevelCountsFromRows($rows);
    }

    /**
     * @param array<array<string, mixed>> $rows
     *
     * @return array{error: int, warning: int, info: int}
     */
    private function mapLevelCountsFromRows(array $rows): array
    {
        $counts = [
            'error' => 0,
            'warning' => 0,
            'info' => 0,
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
