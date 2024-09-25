<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;

#[Package('services-settings')]
class DummyMappingService extends MappingService
{
    final public const DEFAULT_LANGUAGE_UUID = '20080911ffff4fffafffffff19830531';

    public function __construct()
    {
    }

    public function createListItemMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
        ?array $additionalData = null,
        ?string $newUuid = null,
    ): void {
        $uuid = Uuid::randomHex();
        if ($newUuid !== null) {
            $uuid = $newUuid;

            foreach ($this->writeArray as $item) {
                if (
                    $item['connectionId'] === $connectionId
                    && $item['entity'] === $entityName
                    && $item['oldIdentifier'] === $oldIdentifier
                    && $item['entityUuid'] === $newUuid
                ) {
                    return;
                }
            }
        }

        $this->saveListMapping(
            [
                'id' => Uuid::randomHex(),
                'connectionId' => $connectionId,
                'entity' => $entityName,
                'oldIdentifier' => $oldIdentifier,
                'entityUuid' => $uuid,
                'additionalData' => $additionalData,
            ]
        );
    }

    public function writeMapping(): void
    {
    }

    public function getMapping(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?array
    {
        return $this->mappings[\md5($entityName . $oldIdentifier)] ?? null;
    }

    public function getMappingArray(): array
    {
        return $this->mappings;
    }

    public function getMappings(string $connectionId, string $entityName, array $ids, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(SwagMigrationMappingDefinition::ENTITY_NAME, 0, new SwagMigrationMappingCollection(), null, new Criteria(), $context);
    }

    public function getUuidsByEntity(string $connectionId, string $entityName, Context $context): array
    {
        return [];
    }

    public function getValue(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?string
    {
        if (!isset($this->mappings[\md5($entityName . $oldIdentifier)])) {
            return null;
        }

        return $this->mappings[\md5($entityName . $oldIdentifier)]['entityValue'];
    }

    public function getUuidList(string $connectionId, string $entityName, string $identifier, Context $context): array
    {
        return isset($this->mappings[\md5($entityName . $identifier)])
            ? \array_column($this->mappings[\md5($entityName . $identifier)], 'entityUuid')
            : [];
    }

    public function deleteMapping(string $entityUuid, string $connectionId, Context $context): void
    {
        foreach ($this->writeArray as $writeMapping) {
            if ($writeMapping['profile'] === $connectionId && $writeMapping['entityUuid'] === $entityUuid) {
                unset($writeMapping);

                break;
            }
        }
    }

    public function getMigratedSalesChannelUuids(string $connectionId, Context $context): array
    {
        return [];
    }

    protected function saveMapping(array $mapping): void
    {
        $entity = $mapping['entity'];
        $oldIdentifier = $mapping['oldIdentifier'];
        $this->mappings[\md5($entity . $oldIdentifier)] = $mapping;
    }
}
