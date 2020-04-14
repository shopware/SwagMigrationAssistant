<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\Language\LanguageEntity;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface MappingServiceInterface
{
    public function getUuidsByEntity(string $connectionId, string $entityName, Context $context): array;

    public function getValue(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?string;

    public function getOrCreateMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
        ?string $checksum = null,
        ?array $additionalData = null,
        ?string $uuid = null
    ): array;

    public function getMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context
    ): ?array;

    public function createMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        ?string $checksum = null,
        ?array $additionalData = null,
        ?string $uuid = null
    ): array;

    public function updateMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        array $updateData,
        Context $context
    ): array;

    public function createListItemMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
        ?array $additionalData = null,
        ?string $newUuid = null
    ): void;

    public function getUuidList(string $connectionId, string $entityName, string $identifier, Context $context): array;

    public function getDefaultCmsPageUuid(string $connectionId, Context $context): ?string;

    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context, bool $withoutMapping = false): ?string;

    public function getLocaleUuid(string $connectionId, string $localeCode, Context $context): string;

    public function getDefaultLanguage(Context $context): ?LanguageEntity;

    public function getDeliveryTime(string $connectionId, Context $context, int $minValue, int $maxValue, string $unit, string $name): string;

    public function getCountryUuid(string $oldIdentifier, string $iso, string $iso3, string $connectionId, Context $context): ?string;

    public function getCurrencyUuid(string $connectionId, string $oldIsoCode, Context $context): ?string;

    public function getCurrencyUuidWithoutMapping(string $connectionId, string $oldIsoCode, Context $context): ?string;

    public function getTaxUuid(string $connectionId, float $taxRate, Context $context): ?string;

    public function getNumberRangeUuid(string $type, string $oldIdentifier, string $checksum, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getDefaultFolderIdByEntity(string $entityName, MigrationContextInterface $migrationContext, Context $context): ?string;

    public function getThumbnailSizeUuid(int $width, int $height, MigrationContextInterface $migrationContext, Context $context): ?string;

    /**
     * @return string[]
     */
    public function getMigratedSalesChannelUuids(string $connectionId, Context $context): array;

    public function deleteMapping(string $entityUuid, string $connectionId, Context $context): void;

    public function bulkDeleteMapping(array $mappingUuids, Context $context): void;

    public function pushMapping(string $connectionId, string $entity, string $oldIdentifier, string $uuid): void;

    public function pushValueMapping(string $connectionId, string $entity, string $oldIdentifier, string $value): void;

    public function writeMapping(Context $context): void;

    public function getDefaultAvailabilityRule(Context $context): ?string;

    public function getDocumentTypeUuid(string $technicalName, Context $context, MigrationContextInterface $migrationContext): ?string;

    public function getLowestRootCategoryUuid(Context $context): ?string;

    public function getMappings(string $connectionId, string $entityName, array $ids, Context $context): EntitySearchResult;

    public function preloadMappings(array $mappingIds, Context $context): void;
}
