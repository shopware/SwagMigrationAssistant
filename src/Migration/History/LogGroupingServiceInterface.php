<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\History;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
interface LogGroupingServiceInterface
{
    /**
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
    ): array;

    /**
     * @return array<string>
     */
    public function getAllLogIdsByCodeAndEntity(
        string $runId,
        string $code,
        string $entityName,
        string $fieldName,
        ?string $connectionId = null,
    ): array;
}
