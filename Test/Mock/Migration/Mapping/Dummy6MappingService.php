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
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingService;

class Dummy6MappingService extends Shopware6MappingService
{
    public const DEFAULT_CMS_PAGE_UUID = '112155944d6e48aab56471f7df094d53';
    public const DEFAULT_LANGUAGE_UUID = Defaults::LANGUAGE_SYSTEM;
    public const DEFAULT_LOCAL_UUID = '976a257a2cc242988abfcd7e33e16b12';
    public const DEFAULT_DELIVERY_TIME_UUID = 'c2b7cb2bc66a47b9a4e4cf60c9f071fb';
    public const DEFAULT_AVAILABILITY_RULE_UUID = '32a884ed8e9e4c8b9d44a36e14d1a195';
    public const FALLBACK_LOCALE_UUID_FOR_EVERY_CODE = '212ab9a95510421085d9d0009f969236';

    public function __construct()
    {
    }

    public function resetMappingService(): void
    {
        $this->values = [];
        $this->migratedSalesChannels = [];
        $this->writeArray = [];
        $this->languageData = [];
        $this->locales = [];
        $this->mappings = [];
    }

    public function getMapping(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?array
    {
        return $this->mappings[\md5($entityName . $oldIdentifier)] ?? null;
    }

    public function getMappings(string $connectionId, string $entityName, array $ids, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(SwagMigrationMappingDefinition::ENTITY_NAME, 0, new EntityCollection(), null, new Criteria(), $context);
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

    public function bulkDeleteMapping(array $mappingUuids, Context $context): void
    {
    }

    public function writeMapping(Context $context): void
    {
        if (empty($this->writeArray)) {
            return;
        }

        $this->writeArray = [];
        $this->mappings = [];
    }

    public function getDefaultCmsPageUuid(string $connectionId, Context $context): ?string
    {
        return self::DEFAULT_CMS_PAGE_UUID;
    }

    public function getDefaultLanguage(Context $context): ?LanguageEntity
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
        $deliveryTimeMapping = $this->getMapping($connectionId, DefaultEntities::DELIVERY_TIME, $name, $context);

        if ($deliveryTimeMapping !== null) {
            return $deliveryTimeMapping['entityUuid'];
        }

        return self::DEFAULT_DELIVERY_TIME_UUID;
    }

    public function getCountryUuid(string $oldIdentifier, string $iso, string $iso3, string $connectionId, Context $context): ?string
    {
        $countryMapping = $this->getMapping($connectionId, DefaultEntities::COUNTRY, $oldIdentifier, $context);

        if ($countryMapping !== null) {
            return $countryMapping['entityUuid'];
        }

        return null;
    }

    public function getCurrencyUuid(string $connectionId, string $oldIsoCode, Context $context): ?string
    {
        $currencyMapping = $this->getMapping($connectionId, DefaultEntities::CURRENCY, $oldIsoCode, $context);

        if ($currencyMapping !== null) {
            return $currencyMapping['entityUuid'];
        }

        return null;
    }

    public function getCurrencyUuidWithoutMapping(string $connectionId, string $oldIsoCode, Context $context): ?string
    {
        $currencyMapping = $this->getMapping($connectionId, DefaultEntities::CURRENCY, $oldIsoCode, $context);

        if ($currencyMapping !== null) {
            return $currencyMapping['entityUuid'];
        }

        return null;
    }

    public function getTaxUuidByCriteria(string $connectionId, string $sourceId, float $taxRate, string $name, Context $context): ?string
    {
        $taxMapping = $this->getMapping($connectionId, DefaultEntities::TAX, $sourceId, $context);

        if ($taxMapping !== null) {
            return $taxMapping['entityUuid'];
        }

        return null;
    }

    public function getTaxRuleUuidByCriteria(string $connectionId, string $sourceId, string $taxId, string $countryId, string $taxRuleTypeId, Context $context): ?string
    {
        $taxRuleMapping = $this->getMapping($connectionId, DefaultEntities::TAX_RULE, $sourceId, $context);

        if ($taxRuleMapping !== null) {
            return $taxRuleMapping['entityUuid'];
        }

        return null;
    }

    public function getTaxRuleTypeUuidByCriteria(string $connectionId, string $sourceId, string $technicalName, Context $context): ?string
    {
        $taxRuleTypeMapping = $this->getMapping($connectionId, DefaultEntities::TAX_RULE_TYPE, $sourceId, $context);

        if ($taxRuleTypeMapping !== null) {
            return $taxRuleTypeMapping['entityUuid'];
        }

        return null;
    }

    public function getNumberRangeUuid(string $type, string $oldIdentifier, string $checksum, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $numberRangeMapping = $this->getMapping($connection->getId(), DefaultEntities::NUMBER_RANGE, $oldIdentifier, $context);

        if ($numberRangeMapping !== null) {
            return $numberRangeMapping['entityUuid'];
        }

        return null;
    }

    public function getNumberRangeTypeUuid(string $type, string $oldIdentifier, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $numberRangeTypeMapping = $this->getMapping($connection->getId(), DefaultEntities::NUMBER_RANGE_TYPE, $oldIdentifier, $context);

        if ($numberRangeTypeMapping !== null) {
            return $numberRangeTypeMapping['entityUuid'];
        }

        return null;
    }

    public function getMailTemplateTypeUuid(
        string $type,
        string $oldIdentifier,
        MigrationContextInterface $migrationContext,
        Context $context
    ): ?string {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $mailTemplateTypeMapping = $this->getMapping($connection->getId(), DefaultEntities::MAIL_TEMPLATE_TYPE, $oldIdentifier, $context);

        if ($mailTemplateTypeMapping !== null) {
            return $mailTemplateTypeMapping['entityUuid'];
        }

        return null;
    }

    public function getSystemDefaultMailTemplateUuid(string $type, string $oldIdentifier, string $connectionId, MigrationContextInterface $migrationContext, Context $context): string
    {
        $mailTemplateMapping = $this->getMapping($connectionId, DefaultEntities::MAIL_TEMPLATE, $oldIdentifier, $context);

        if ($mailTemplateMapping) {
            return $mailTemplateMapping['entityUuid'];
        }

        return $oldIdentifier;
    }

    public function getDefaultFolderIdByEntity(string $entityName, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $connectionId = $connection->getId();
        $defaultFolderMapping = $this->getMapping($connectionId, DefaultEntities::MEDIA_DEFAULT_FOLDER, $entityName, $context);

        if ($defaultFolderMapping !== null) {
            return $defaultFolderMapping['entityUuid'];
        }

        return null;
    }

    public function getSalutationUuid(string $oldIdentifier, string $salutationKey, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $salutationMapping = $this->getMapping($connection->getId(), DefaultEntities::SALUTATION, $oldIdentifier, $context);

        if ($salutationMapping !== null) {
            return $salutationMapping['entityUuid'];
        }

        return null;
    }

    public function getThumbnailSizeUuid(int $width, int $height, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }
        $connectionId = $connection->getId();

        $sizeString = $width . '-' . $height;
        $thumbnailSizeMapping = $this->getMapping($connectionId, DefaultEntities::MEDIA_THUMBNAIL_SIZE, $sizeString, $context);

        if ($thumbnailSizeMapping !== null) {
            return $thumbnailSizeMapping['entityUuid'];
        }

        return null;
    }

    public function getMigratedSalesChannelUuids(string $connectionId, Context $context): array
    {
        return [];
    }

    public function getDefaultAvailabilityRule(Context $context): ?string
    {
        return self::DEFAULT_AVAILABILITY_RULE_UUID;
    }

    public function getDocumentTypeUuid(string $technicalName, Context $context, MigrationContextInterface $migrationContext): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $connectionId = $connection->getId();
        $documentTypeMapping = $this->getMapping($connectionId, DefaultEntities::ORDER_DOCUMENT_TYPE, $technicalName, $context);

        if ($documentTypeMapping !== null) {
            return $documentTypeMapping['entityUuid'];
        }

        return null;
    }

    public function getGlobalDocumentBaseConfigUuid(string $oldIdentifier, string $documentTypeId, string $connectionId, MigrationContextInterface $migrationContext, Context $context): string
    {
        $baseConfigMapping = $this->getMapping($connectionId, DefaultEntities::ORDER_DOCUMENT_BASE_CONFIG, $oldIdentifier, $context);

        if ($baseConfigMapping) {
            return $baseConfigMapping['entityUuid'];
        }

        return $oldIdentifier;
    }

    public function getCmsPageUuidByNames(array $names, Context $context): ?string
    {
        $cmsPageMapping = $this->getMapping('global', DefaultEntities::CMS_PAGE, 'isDuplicate', $context);

        if ($cmsPageMapping) {
            return $cmsPageMapping['entityUuid'];
        }

        return null;
    }

    public function getLowestRootCategoryUuid(Context $context): ?string
    {
        return null;
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

    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context, bool $withoutMapping = false): ?string
    {
        if (isset($this->languageData[$localeCode])) {
            return $this->languageData[$localeCode];
        }

        $languageUuid = $this->searchLanguageInMapping($connectionId, $localeCode, $context);
        if ($languageUuid !== null) {
            return $languageUuid;
        }

        return null;
    }

    public function getLocaleUuid(string $connectionId, string $localeCode, Context $context): string
    {
        if (isset($this->locales[$localeCode])) {
            return $this->locales[$localeCode];
        }

        $localeMapping = $this->getMapping($connectionId, DefaultEntities::LOCALE, $localeCode, $context);

        if ($localeMapping !== null) {
            $this->locales[$localeCode] = $localeMapping['entityUuid'];

            return $localeMapping['entityUuid'];
        }

        $localeUuid = $this->searchLocale($localeCode, $context);
        $this->locales[$localeCode] = $localeUuid;

        return $localeUuid;
    }

    public function getSystemConfigUuid(string $oldIdentifier, string $configurationKey, ?string $salesChannelId, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }
        $connectionId = $connection->getId();

        $mapping = $this->getMapping($connectionId, DefaultEntities::SYSTEM_CONFIG, $oldIdentifier, $context);

        if ($mapping !== null) {
            return $mapping['entityUuid'];
        }

        return null;
    }

    public function getProductSortingUuid(string $key, Context $context): array
    {
        $mapping = $this->getMapping('dummy-connection-id', DefaultEntities::PRODUCT_SORTING, $key, $context);
        $id = $mapping['entityUuid'] ?? null;
        $isLocked = $key === 'is-locked';

        return [$id, $isLocked];
    }

    public function getStateMachineStateUuid(string $oldIdentifier, string $technicalName, string $stateMachineTechnicalName, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }
        $connectionId = $connection->getId();

        $mapping = $this->getMapping($connectionId, DefaultEntities::STATE_MACHINE_STATE, $oldIdentifier, $context);

        if ($mapping !== null) {
            return $mapping['entityUuid'];
        }

        return null;
    }

    public function getCountryStateUuid(string $oldIdentifier, string $countryIso, string $countryIso3, string $countryStateCode, string $connectionId, Context $context): ?string
    {
        $mapping = $this->getMapping($connectionId, DefaultEntities::COUNTRY_STATE, $oldIdentifier, $context);

        if ($mapping !== null) {
            return $mapping['entityUuid'];
        }

        return $oldIdentifier;
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

    private function searchLanguageInMapping(string $connectionId, string $localeCode, Context $context): ?string
    {
        $mapping = $this->getMapping($connectionId, DefaultEntities::LANGUAGE, $localeCode, $context);

        if ($mapping !== null) {
            return $mapping['entityUuid'];
        }

        return null;
    }

    private function searchLocale(string $localeCode, Context $context): string
    {
        return self::FALLBACK_LOCALE_UUID_FOR_EVERY_CODE;
    }
}
