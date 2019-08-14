<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Mapping;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Locale\LocaleEntity;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class DummyMappingService extends MappingService
{
    public const DEFAULT_LANGUAGE_UUID = '20080911ffff4fffafffffff19830531';
    public const DEFAULT_LOCAL_UUID = '20080911ffff4fffafffffff19830531';

    public function __construct()
    {
    }

    public function readExistingMappings(Context $context): void
    {
    }

    public function createNewUuid(
        string $connectionId,
        string $entityName,
        string $oldId,
        Context $context,
        ?array $additionalData = null,
        ?string $newUuid = null
    ): string {
        $uuid = $this->getUuid($connectionId, $entityName, $oldId, $context);
        if ($uuid !== null) {
            return $uuid;
        }

        $uuid = Uuid::randomHex();
        if ($newUuid !== null) {
            $uuid = $newUuid;
        }

        $this->uuids[$entityName][$oldId] = $uuid;

        return $uuid;
    }

    public function writeMapping(Context $context): void
    {
    }

    public function saveMapping(array $mapping): void
    {
    }

    public function setProfile(string $profileName): void
    {
    }

    public function getUuid(string $connectionId, string $entityName, string $oldId, Context $context): ?string
    {
        return $this->uuids[$entityName][$oldId] ?? null;
    }

    public function getUuidsByEntity(string $connectionId, string $entityName, Context $context): array
    {
        return [];
    }

    public function getValue(string $connectionId, string $entityName, string $oldId, Context $context): ?string
    {
        return $this->values[$entityName][$oldId] ?? null;
    }

    public function getUuidList(string $connectionId, string $entityName, string $identifier, Context $context): array
    {
        return $this->uuids[$entityName][$identifier] ?? [];
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

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $connectionId, Context $context): ?string
    {
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

    public function getPrivateUuidArray(): array
    {
        return $this->uuids;
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

    public function getDefaultFolderIdByEntity(string $entityName, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        return Uuid::randomHex();
    }

    public function getThumbnailSizeUuid(int $width, int $height, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        return null;
    }

    public function getNumberRangeUuid(string $type, string $oldId, MigrationContextInterface $migrationContext, Context $context): ?string
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

    public function getDefaultSalesChannelTheme(string $connectionId, Context $context): ?string
    {
        return Uuid::randomHex();
    }
}
