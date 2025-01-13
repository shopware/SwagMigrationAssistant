<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;

/**
 * @phpstan-type MappingStruct array{id: string, connectionId: string, oldIdentifier: ?string, entityUuid: ?string, entityValue: ?string, checksum: ?string, additionalData: ?array<mixed>}
 */
#[Package('fundamentals@after-sales')]
interface MappingServiceInterface
{
    public function getUuidsByEntity(string $connectionId, string $entityName, Context $context): array;

    public function getValue(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?string;

    /**
     * @param array<mixed>|null $additionalData
     *
     * @return MappingStruct
     */
    public function getOrCreateMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
        ?string $checksum = null,
        ?array $additionalData = null,
        ?string $uuid = null,
        ?string $entityValue = null,
    ): array;

    /**
     * @return ?MappingStruct
     */
    public function getMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
    ): ?array;

    /**
     * @param array<mixed>|null $additionalData
     *
     * @return MappingStruct
     */
    public function createMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        ?string $checksum = null,
        ?array $additionalData = null,
        ?string $uuid = null,
        ?string $entityValue = null,
    ): array;

    public function updateMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        array $updateData,
        Context $context,
    ): array;

    /**
     * @return string[]
     */
    public function getMigratedSalesChannelUuids(string $connectionId, Context $context): array;

    public function deleteMapping(string $entityUuid, string $connectionId, Context $context): void;

    public function writeMapping(): void;

    /**
     * @return EntitySearchResult<SwagMigrationMappingCollection>
     */
    public function getMappings(string $connectionId, string $entityName, array $ids, Context $context): EntitySearchResult;

    public function preloadMappings(array $mappingIds, Context $context): void;
}
