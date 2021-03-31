<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Mapping;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class DummyMappingService extends MappingService
{
    public const DEFAULT_LANGUAGE_UUID = '20080911ffff4fffafffffff19830531';
    public const DEFAULT_LOCAL_UUID = '20080911ffff4fffafffffff19830531';
    public const DEFAULT_GERMANY_UUID = '20080911ffff4fffafffffff19830511';
    public const DEFAULT_UK_UUID = '20080911ffff4fffafffffff19830512';

    public function __construct()
    {
    }

    public function createListItemMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
        ?array $additionalData = null,
        ?string $newUuid = null
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

    public function writeMapping(Context $context): void
    {
    }

    public function saveMapping(array $mapping): void
    {
        $entity = $mapping['entity'];
        $oldIdentifier = $mapping['oldIdentifier'];
        $this->mappings[\md5($entity . $oldIdentifier)] = $mapping;
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
        return new EntitySearchResult(SwagMigrationMappingDefinition::ENTITY_NAME, 0, new EntityCollection(), null, new Criteria(), $context);
    }

    public function getUuidsByEntity(string $connectionId, string $entityName, Context $context): array
    {
        return [];
    }

    public function getValue(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?string
    {
        return $this->values[$entityName][$oldIdentifier] ?? null;
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

    public function pushMapping(string $connectionId, string $entity, string $oldIdentifier, string $uuid): void
    {
        $this->uuids[$entity][$oldIdentifier] = $uuid;
    }

    public function bulkDeleteMapping(array $mappingUuids, Context $context): void
    {
    }

    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context, bool $withoutMapping = false): ?string
    {
        return self::DEFAULT_LANGUAGE_UUID;
    }

    public function getMigratedSalesChannelUuids(string $connectionId, Context $context): array
    {
        return [];
    }

    public function getCountryUuid(string $oldIdentifier, string $iso, string $iso3, string $connectionId, Context $context): ?string
    {
        if ($iso === 'DE') {
            return self::DEFAULT_GERMANY_UUID;
        }

        if ($iso === 'GB') {
            return self::DEFAULT_UK_UUID;
        }

        return null;
    }

    public function getCurrencyUuid(string $connectionId, string $oldIsoCode, Context $context): ?string
    {
        return Defaults::CURRENCY;
    }

    public function getTaxUuid(string $connectionId, float $taxRate, Context $context): ?string
    {
        return null;
    }

    public function getDefaultAvailabilityRule(Context $context): string
    {
        return Uuid::randomHex();
    }

    public function getDefaultLanguage(Context $context): LanguageEntity
    {
        $defaultLanguage = new LanguageEntity();
        $locale = new LocaleEntity();
        $defaultLanguage->assign([
            'id' => self::DEFAULT_LANGUAGE_UUID,
            'locale' => $locale->assign([
                'id' => self::DEFAULT_LOCAL_UUID,
                'code' => 'en-GB',
            ]),
        ]);

        return $defaultLanguage;
    }

    public function getDeliveryTime(string $connectionId, Context $context, int $minValue, int $maxValue, string $unit, string $name): string
    {
        return Uuid::randomHex();
    }

    public function getDefaultFolderIdByEntity(string $entityName, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        return Uuid::randomHex();
    }

    public function getThumbnailSizeUuid(int $width, int $height, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        return null;
    }

    public function getNumberRangeUuid(string $type, string $oldIdentifier, string $checksum, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        return Uuid::randomHex();
    }

    public function getCurrencyUuidWithoutMapping(string $connectionId, string $oldIsoCode, Context $context): ?string
    {
        return Uuid::randomHex();
    }

    public function getLowestRootCategoryUuid(Context $context): ?string
    {
        return null;
    }

    public function getDefaultCmsPageUuid(string $connectionId, Context $context): ?string
    {
        return Uuid::randomHex();
    }

    public function getDocumentTypeUuid(string $technicalName, Context $context, MigrationContextInterface $migrationContext): ?string
    {
        return null;
    }
}
