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

        $queryParams = $this->buildQueryParameters(
            $runUuid,
            $level,
            $page,
            $limit,
            $connectionIdBytes,
            $filterCode,
            $filterEntity,
            $filterField
        );

        $sql = $this->buildGroupedLogsQuery(
            $sortBy,
            $sortDirection,
            $filterStatus,
            $queryParams
        );

        $result = $this->connection->executeQuery(
            $sql,
            $queryParams['params'],
            $queryParams['types']
        );

        $rows = $result->fetchAllAssociative();
        $groupedLogs = $this->mapLogsFromRows($rows);
        $total = \count($rows) > 0 ? (int) $rows[0]['total'] : 0;

        $levelCounts = $this->getLogLevelCounts(
            $runUuid,
            $filterCode,
            $filterEntity,
            $filterField,
            $filterStatus,
            $connectionIdBytes
        );

        return [
            'total' => $total,
            'items' => $groupedLogs,
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
    ): array {
        $sql = '
            SELECT LOWER(HEX(id)) as id
            FROM swag_migration_logging
            WHERE code = :code
                AND entity_name = :entityName
                AND field_name = :fieldName
                AND user_fixable = 1
        ';

        $result = $this->connection->executeQuery(
            $sql,
            [
                'code' => $code,
                'entityName' => $entityName,
                'fieldName' => $fieldName,
            ]
        );

        $rows = $result->fetchAllAssociative();

        return \array_column($rows, 'id');
    }

    /**
     * @return array{params: array<string, mixed>, types: array<string, ParameterType>, whereClause: string}
     */
    private function buildQueryParameters(
        string $runUuid,
        string $level,
        int $page,
        int $limit,
        string $connectionIdBytes,
        ?string $filterCode,
        ?string $filterEntity,
        ?string $filterField,
    ): array {
        $runIdBytes = Uuid::fromHexToBytes($runUuid);
        $offset = ($page - 1) * $limit;

        $params = [
            'runId' => $runIdBytes,
            'level' => $level,
            'limit' => $limit,
            'offset' => $offset,
            'connectionId' => $connectionIdBytes,
        ];

        $whereConditions = $this->buildFilterConditions(
            $filterCode,
            $filterEntity,
            $filterField,
            $params
        );

        return [
            'params' => $params,
            'types' => [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ],
            'whereClause' => $whereConditions ? ' AND ' . \implode(' AND ', $whereConditions) : '',
        ];
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string>
     */
    private function buildFilterConditions(?string $filterCode, ?string $filterEntity, ?string $filterField, array &$params): array
    {
        $conditions = [];

        if ($filterCode !== null) {
            $conditions[] = 'l.code = :filterCode';
            $params['filterCode'] = $filterCode;
        }

        if ($filterEntity !== null) {
            $conditions[] = 'l.entity_name = :filterEntity';
            $params['filterEntity'] = $filterEntity;
        }

        if ($filterField !== null) {
            $conditions[] = 'l.field_name = :filterField';
            $params['filterField'] = $filterField;
        }

        return $conditions;
    }

    /**
     * @param array{params: array<string, mixed>, types: array<string, ParameterType>, whereClause: string} $queryParams
     */
    private function buildGroupedLogsQuery(string $sortBy, string $sortDirection, ?string $filterStatus, array $queryParams): string
    {
        $whereClause = $queryParams['whereClause'];
        $havingClause = $this->buildHavingClause($filterStatus);
        $orderByClause = $this->buildOrderByClause($sortBy, $sortDirection);
        $fixJoinClause = $this->getFixJoinClause();
        $countSubquery = $this->buildCountSubquery(
            $filterStatus,
            $whereClause,
            $havingClause,
            $fixJoinClause
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
            {$fixJoinClause}
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

    private function buildCountSubquery(?string $filterStatus, string $whereClause, string $havingClause, string $fixJoinClause): string
    {
        $fixJoinForSubquery = $filterStatus !== null ? $fixJoinClause : '';

        $subqueryWhere = \str_replace(['l.', 'f.'], ['l2.', 'f2.'], $whereClause);
        $subqueryHaving = $filterStatus !== null ? \str_replace(['l.', 'f.'], ['l2.', 'f2.'], $havingClause) : '';
        $subqueryJoin = \str_replace(['l.', 'f.'], ['l2.', 'f2.'], $fixJoinForSubquery);

        return "(
                SELECT COUNT(*)
                FROM (
                    SELECT 1
                    FROM swag_migration_logging l2
                    {$subqueryJoin}
                    WHERE l2.run_id = :runId
                        AND l2.level = :level
                        AND l2.user_fixable = 1
                        {$subqueryWhere}
                    GROUP BY l2.code, l2.entity_name, l2.field_name
                    {$subqueryHaving}
                ) as grouped_logs
            )";
    }

    private function getFixJoinClause(): string
    {
        return 'LEFT JOIN swag_migration_fix f ON (
                f.connection_id = :connectionId
                AND f.entity_name = l.entity_name
                AND f.path = l.field_name
                AND f.entity_id = UNHEX(JSON_UNQUOTE(JSON_EXTRACT(l.converted_data, "$.id")))
            )';
    }

    private function buildHavingClause(?string $filterStatus): string
    {
        if ($filterStatus === 'resolved') {
            return ' HAVING COUNT(DISTINCT l.id) > 0 AND COUNT(DISTINCT l.id) = COUNT(DISTINCT f.id)';
        }

        if ($filterStatus === 'unresolved') {
            return ' HAVING COUNT(DISTINCT l.id) = 0 OR COUNT(DISTINCT l.id) != COUNT(DISTINCT f.id)';
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
     * @throws Exception
     *
     * @return array{error: int, warning: int, info: int}
     */
    private function getLogLevelCounts(
        string $runUuid,
        ?string $filterCode = null,
        ?string $filterEntity = null,
        ?string $filterField = null,
        ?string $filterStatus = null,
        ?string $connectionIdBytes = null,
    ): array {
        $runIdBytes = Uuid::fromHexToBytes($runUuid);
        $params = ['runId' => $runIdBytes];

        $whereConditions = $this->buildFilterConditions(
            $filterCode,
            $filterEntity,
            $filterField,
            $params
        );

        $additionalWhere = $whereConditions ? ' AND ' . \implode(' AND ', $whereConditions) : '';

        $joinClause = '';
        $havingClause = '';
        $groupByClause = ', l.code, l.entity_name, l.field_name';

        if ($filterStatus !== null && $connectionIdBytes !== null) {
            $params['connectionId'] = $connectionIdBytes;
            $joinClause = $this->getFixJoinClause();
            $havingClause = $this->buildHavingClause($filterStatus);
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
                    {$additionalWhere}
                GROUP BY l.level{$groupByClause}
                {$havingClause}
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
