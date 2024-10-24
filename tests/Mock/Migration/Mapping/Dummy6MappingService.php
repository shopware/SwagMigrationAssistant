<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Mapping;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;

#[Package('services-settings')]
class Dummy6MappingService extends MappingService
{
    final public const DEFAULT_LANGUAGE_UUID = Defaults::LANGUAGE_SYSTEM;
    final public const DEFAULT_DELIVERY_TIME_UUID = 'c2b7cb2bc66a47b9a4e4cf60c9f071fb';
    final public const FALLBACK_LOCALE_UUID_FOR_EVERY_CODE = '212ab9a95510421085d9d0009f969236';

    public function __construct()
    {
    }

    public function getMapping(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?array
    {
        return $this->mappings[$entityName . $oldIdentifier] ?? null;
    }

    public function getMappings(string $connectionId, string $entityName, array $ids, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(SwagMigrationMappingDefinition::ENTITY_NAME, 0, new SwagMigrationMappingCollection(), null, new Criteria(), $context);
    }

    public function preloadMappings(array $mappingIds, Context $context): void
    {
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
        foreach ($this->writeArray as $key => $writeMapping) {
            if ($writeMapping['connectionId'] === $connectionId && $writeMapping['entityUuid'] === $entityUuid) {
                unset($this->writeArray[$key]);
                $this->writeArray = \array_values($this->writeArray);

                break;
            }
        }

        foreach ($this->mappings as $hash => $mapping) {
            if ($mapping['entityUuid'] === $entityUuid) {
                unset($this->mappings[$hash]);
            }
        }
    }

    public function writeMapping(): void
    {
        if (empty($this->writeArray)) {
            return;
        }

        $this->writeArray = [];
        $this->mappings = [];
    }

    public function getMigratedSalesChannelUuids(string $connectionId, Context $context): array
    {
        return [];
    }

    public function createListItemMapping(string $connectionId, string $entityName, string $oldIdentifier, Context $context, ?array $additionalData = null, ?string $newUuid = null): void
    {
        $uuid = Uuid::randomHex();
        if ($newUuid !== null) {
            $uuid = $newUuid;

            if ($this->isUuidDuplicate($connectionId, $entityName, $oldIdentifier, $newUuid, $context)) {
                return;
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

    private function isUuidDuplicate(string $connectionId, string $entityName, string $id, string $uuid, Context $context): bool
    {
        foreach ($this->writeArray as $item) {
            if (
                $item['connectionId'] === $connectionId
                && $item['entity'] === $entityName
                && $item['oldIdentifier'] === $id
                && $item['entityUuid'] === $uuid
            ) {
                return true;
            }
        }

        return false;
    }
}
