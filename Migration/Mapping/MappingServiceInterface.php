<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Language\LanguageEntity;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface MappingServiceInterface
{
    public function getUuid(string $connectionId, string $entityName, string $oldId, Context $context): ?string;

    public function createNewUuidListItem(
        string $connectionId,
        string $entityName,
        string $oldId,
        ?array $additionalData = null,
        ?string $newUuid = null
    ): string;

    public function getUuidList(string $connectionId, string $entityName, string $identifier, Context $context): array;

    public function createNewUuid(
        string $connectionId,
        string $entityName,
        string $oldId,
        Context $context,
        ?array $additionalData = null,
        ?string $newUuid = null
    ): string;

    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context): ?string;

    public function getLocaleUuid(string $connectionId, string $localeCode, Context $context): string;

    public function getDefaultLanguage(Context $context): LanguageEntity;

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $connectionId, Context $context): ?string;

    public function getCurrencyUuid(string $connectionId, string $oldShortName, Context $context): ?string;

    public function getTaxUuid(string $connectionId, float $taxRate, Context $context): ?string;

    public function getNumberRangeUuid(string $type, string $oldId, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getDefaultFolderIdByEntity(string $entityName, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getThumbnailSizeUuid(int $width, int $height, MigrationContextInterface $migrationContext, Context $context): ?string;

    /**
     * @return string[]
     */
    public function getMigratedSalesChannelUuids(string $connectionId, Context $context): array;

    public function deleteMapping(string $entityUuid, string $connectionId, Context $context): void;

    public function bulkDeleteMapping(array $mappingUuids, Context $context): void;

    public function pushMapping(string $connectionId, string $entity, string $oldIdentifier, string $uuid);

    public function writeMapping(Context $context): void;

    public function getDefaultAvailabilityRule(Context $context): ?string;
}
