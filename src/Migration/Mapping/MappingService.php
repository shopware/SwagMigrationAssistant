<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Content\Cms\CmsPageDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderCollection;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeCollection;
use Shopware\Core\Content\Rule\RuleCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\DeliveryTime\DeliveryTimeCollection;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleCollection;
use Shopware\Core\System\NumberRange\NumberRangeCollection;
use Shopware\Core\System\Tax\TaxCollection;
use SwagMigrationAssistant\Exception\LocaleNotFoundException;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Contracts\Service\ResetInterface;

#[Package('services-settings')]
class MappingService implements MappingServiceInterface, ResetInterface
{
    protected array $migratedSalesChannels = [];

    protected array $writeArray = [];

    protected array $languageData = [];

    protected array $locales = [];

    protected array $mappings = [];

    protected LanguageEntity $defaultLanguageData;

    /**
     * @param EntityRepository<SwagMigrationMappingCollection> $migrationMappingRepo
     * @param EntityRepository<LocaleCollection> $localeRepository
     * @param EntityRepository<LanguageCollection> $languageRepository
     * @param EntityRepository<CountryCollection> $countryRepository
     * @param EntityRepository<CurrencyCollection> $currencyRepository
     * @param EntityRepository<TaxCollection> $taxRepo
     * @param EntityRepository<NumberRangeCollection> $numberRangeRepo
     * @param EntityRepository<RuleCollection> $ruleRepo
     * @param EntityRepository<MediaThumbnailSizeCollection> $thumbnailSizeRepo
     * @param EntityRepository<MediaDefaultFolderCollection> $mediaDefaultFolderRepo
     * @param EntityRepository<CategoryCollection> $categoryRepo
     * @param EntityRepository<CmsPageCollection> $cmsPageRepo
     * @param EntityRepository<DeliveryTimeCollection> $deliveryTimeRepo
     * @param EntityRepository<DocumentTypeCollection> $documentTypeRepo
     */
    public function __construct(
        protected EntityRepository $migrationMappingRepo,
        protected EntityRepository $localeRepository,
        protected EntityRepository $languageRepository,
        protected EntityRepository $countryRepository,
        protected EntityRepository $currencyRepository,
        protected EntityRepository $taxRepo,
        protected EntityRepository $numberRangeRepo,
        protected EntityRepository $ruleRepo,
        protected EntityRepository $thumbnailSizeRepo,
        protected EntityRepository $mediaDefaultFolderRepo,
        protected EntityRepository $categoryRepo,
        protected EntityRepository $cmsPageRepo,
        protected EntityRepository $deliveryTimeRepo,
        protected EntityRepository $documentTypeRepo,
        protected EntityWriterInterface $entityWriter,
        protected EntityDefinition $mappingDefinition,
        protected LoggerInterface $logger
    ) {
    }

    public function reset(): void
    {
        if (!empty($this->writeArray)) {
            $this->logger->error('SwagMigrationAssistant: Migration mapping was not empty on calling reset.');
        }

        $this->writeArray = [];
        $this->languageData = [];
        $this->locales = [];
        $this->mappings = [];
        $this->migratedSalesChannels = [];
    }

    public function getOrCreateMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
        ?string $checksum = null,
        ?array $additionalData = null,
        ?string $uuid = null,
        ?string $entityValue = null,
    ): array {
        $mapping = $this->getMapping($connectionId, $entityName, $oldIdentifier, $context);

        if (!isset($mapping)) {
            return $this->createMapping($connectionId, $entityName, $oldIdentifier, $checksum, $additionalData, $uuid, $entityValue);
        }

        if ($additionalData !== null) {
            $mapping['additionalData'] = $additionalData;
        }

        if ($uuid !== null) {
            $mapping['entityUuid'] = $uuid;
        }

        if ($entityValue !== null) {
            $mapping['entityValue'] = $entityValue;
        }

        if (
            $uuid !== null
            || $additionalData !== null
            || $entityValue !== null
        ) {
            $this->saveMapping($mapping);
        }

        return $mapping;
    }

    public function getMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context
    ): ?array {
        if (isset($this->mappings[\md5($entityName . $oldIdentifier)])) {
            return $this->mappings[\md5($entityName . $oldIdentifier)];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $oldIdentifier));
        $criteria->setLimit(1);

        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $element = $result->getEntities()->first();

            if ($element === null) {
                return null;
            }

            $mapping = [
                'id' => $element->getId(),
                'connectionId' => $element->getConnectionId(),
                'entity' => $element->getEntity(),
                'oldIdentifier' => $element->getOldIdentifier(),
                'entityUuid' => $element->getEntityUuid(),
                'entityValue' => $element->getEntityValue(),
                'checksum' => $element->getChecksum(),
                'additionalData' => $element->getAdditionalData(),
            ];
            $this->mappings[\md5($entityName . $oldIdentifier)] = $mapping;

            return $mapping;
        }

        return null;
    }

    public function createMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        ?string $checksum = null,
        ?array $additionalData = null,
        ?string $uuid = null,
        ?string $entityValue = null,
    ): array {
        $fallbackEntityUuid = $entityValue !== null ? null : Uuid::randomHex();

        $mapping = [
            'id' => Uuid::randomHex(),
            'connectionId' => $connectionId,
            'entity' => $entityName,
            'oldIdentifier' => $oldIdentifier,
            'entityUuid' => $uuid ?? $fallbackEntityUuid,
            'entityValue' => $entityValue,
            'checksum' => $checksum,
            'additionalData' => $additionalData,
        ];
        $this->saveMapping($mapping);

        return $mapping;
    }

    public function updateMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        array $updateData,
        Context $context
    ): array {
        $mapping = $this->getMapping($connectionId, $entityName, $oldIdentifier, $context);

        if ($mapping === null) {
            return $this->createMapping(
                $connectionId,
                $entityName,
                $oldIdentifier,
                $updateData['checksum'] ?? null,
                $updateData['additionalData'] ?? null,
                $updateData['entityUuid'] ?? null
            );
        }

        $mapping = \array_merge($mapping, $updateData);
        $this->saveMapping($mapping);

        return $mapping;
    }

    public function getMappings(string $connectionId, string $entityName, array $ids, Context $context): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsAnyFilter('oldIdentifier', $ids));

        return $this->migrationMappingRepo->search($criteria, $context);
    }

    public function preloadMappings(array $mappingIds, Context $context): void
    {
        if (empty($mappingIds)) {
            return;
        }

        $result = $this->migrationMappingRepo->search(new Criteria($mappingIds), $context);

        if ($result->count() > 0) {
            $elements = $result->getEntities()->getElements();
            foreach ($elements as $mapping) {
                $entityName = $mapping->getEntity();
                $oldIdentifier = $mapping->getOldIdentifier();
                $this->mappings[\md5($entityName . $oldIdentifier)] = [
                    'id' => $mapping->getId(),
                    'connectionId' => $mapping->getConnectionId(),
                    'entity' => $entityName,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $mapping->getEntityUuid(),
                    'entityValue' => $mapping->getEntityValue(),
                    'checksum' => $mapping->getChecksum(),
                    'additionalData' => $mapping->getAdditionalData(),
                ];
            }
            unset($result);
        }
    }

    public function getUuidsByEntity(string $connectionId, string $entityName, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));

        $entities = $this->migrationMappingRepo->search($criteria, $context)->getEntities();

        $entityUuids = [];
        foreach ($entities as $entity) {
            $entityUuids[] = $entity->getEntityUuid();
        }

        return $entityUuids;
    }

    public function getValue(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?string
    {
        if (isset($this->mappings[\md5($entityName . $oldIdentifier)])) {
            return $this->mappings[\md5($entityName . $oldIdentifier)]['entityValue'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $oldIdentifier));
        $criteria->setLimit(1);

        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $element = $result->getEntities()->first();

            if ($element === null) {
                return null;
            }

            $value = $element->getEntityValue();

            $mapping = [
                'id' => $element->getId(),
                'connectionId' => $element->getConnectionId(),
                'entity' => $element->getEntity(),
                'oldIdentifier' => $element->getOldIdentifier(),
                'entityUuid' => $element->getEntityUuid(),
                'entityValue' => $value,
                'checksum' => $element->getChecksum(),
                'additionalData' => $element->getAdditionalData(),
            ];
            $this->mappings[\md5($entityName . $oldIdentifier)] = $mapping;

            return $value;
        }

        return null;
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
                'entityValue' => null,
                'checksum' => null,
                'additionalData' => $additionalData,
            ]
        );
    }

    public function getUuidList(string $connectionId, string $entityName, string $identifier, Context $context): array
    {
        if (isset($this->mappings[\md5($entityName . $identifier)])) {
            return $this->mappings[\md5($entityName . $identifier)];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $identifier));

        $result = $this->migrationMappingRepo->search($criteria, $context);

        $uuidList = [];
        if ($result->getTotal() > 0) {
            foreach ($result->getEntities() as $entity) {
                $uuidList[] = $entity->getEntityUuid();
            }
        }

        $this->mappings[\md5($entityName . $identifier)] = $uuidList;

        return $uuidList;
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
            if (isset($mapping['entityUuid']) && $mapping['entityUuid'] === $entityUuid) {
                unset($this->mappings[$hash]);
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entityUuid', $entityUuid));
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->setLimit(1);

        $result = $this->migrationMappingRepo->searchIds($criteria, $context);

        if ($result->getTotal() > 0) {
            $this->migrationMappingRepo->delete(\array_values($result->getData()), $context);
        }
    }

    public function writeMapping(Context $context): void
    {
        if (empty($this->writeArray)) {
            return;
        }

        try {
            $this->entityWriter->upsert(
                $this->mappingDefinition,
                $this->writeArray,
                WriteContext::createFromContext($context)
            );
        } catch (\Exception) {
            $this->writePerEntry($context);
        } finally {
            $this->writeArray = [];
            // This should not really be necessary
            // but removing it could increase memory usage / needs profiling
            $this->mappings = [];
        }
    }

    public function getDefaultCmsPageUuid(string $connectionId, Context $context): ?string
    {
        $cmsPageMapping = $this->getMapping($connectionId, CmsPageDefinition::ENTITY_NAME, 'default_cms_page', $context);
        if ($cmsPageMapping !== null) {
            return $cmsPageMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', 'product_list'));
        $criteria->addFilter(new EqualsFilter('locked', true));

        $cmsPage = $this->cmsPageRepo->search($criteria, $context)->getEntities()->first();

        if ($cmsPage === null) {
            return null;
        }

        $uuid = $cmsPage->getId();

        $this->saveMapping(
            [
                'id' => Uuid::randomHex(),
                'connectionId' => $connectionId,
                'entity' => CmsPageDefinition::ENTITY_NAME,
                'oldIdentifier' => 'default_cms_page',
                'entityUuid' => $uuid,
                'entityValue' => null,
                'checksum' => null,
                'additionalData' => null,
            ]
        );

        return $uuid;
    }

    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context, bool $withoutMapping = false): ?string
    {
        if (!$withoutMapping && isset($this->languageData[$localeCode])) {
            return $this->languageData[$localeCode];
        }

        $languageUuid = $this->searchLanguageInMapping($localeCode, $context);
        if (!$withoutMapping && $languageUuid !== null) {
            return $languageUuid;
        }

        $localeUuid = $this->searchLocale($localeCode, $context);

        $languageUuid = $this->searchLanguageByLocale($localeUuid, $context);

        if ($languageUuid === null) {
            return $languageUuid;
        }
        $this->languageData[$localeCode] = $languageUuid;

        return $languageUuid;
    }

    public function getLocaleUuid(string $connectionId, string $localeCode, Context $context): string
    {
        if (isset($this->locales[$localeCode])) {
            return $this->locales[$localeCode];
        }

        $localeMapping = $this->getMapping($connectionId, DefaultEntities::LOCALE, $localeCode, $context);

        if ($localeMapping !== null) {
            $this->locales[$localeCode] = $localeMapping['entityUuid'];

            return (string) $localeMapping['entityUuid'];
        }

        $localeUuid = $this->searchLocale($localeCode, $context);
        $this->locales[$localeCode] = $localeUuid;

        return $localeUuid;
    }

    public function getDefaultLanguage(Context $context): ?LanguageEntity
    {
        if (!empty($this->defaultLanguageData)) {
            return $this->defaultLanguageData;
        }

        $languageUuid = $context->getLanguageId();

        $criteria = new Criteria([$languageUuid]);
        $criteria->addAssociation('locale');

        $language = $this->languageRepository->search($criteria, $context)->first();

        if (!$language instanceof LanguageEntity) {
            return null;
        }

        $this->defaultLanguageData = $language;

        return $language;
    }

    public function getDeliveryTime(string $connectionId, Context $context, int $minValue, int $maxValue, string $unit, string $name): string
    {
        $deliveryTimeMapping = $this->getMapping($connectionId, DefaultEntities::DELIVERY_TIME, $name, $context);

        if ($deliveryTimeMapping !== null) {
            return (string) $deliveryTimeMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('min', $minValue));
        $criteria->addFilter(new EqualsFilter('max', $maxValue));
        $criteria->addFilter(new EqualsFilter('unit', $unit));
        $criteria->setLimit(1);

        $result = $this->deliveryTimeRepo->searchIds($criteria, $context);

        $deliveryTimeUuid = $result->firstId();

        if ($deliveryTimeUuid === null) {
            $deliveryTimeUuid = Uuid::isValid($name) ? $name : Uuid::randomHex();
        }

        $this->saveMapping(
            [
                'id' => Uuid::randomHex(),
                'connectionId' => $connectionId,
                'entity' => DefaultEntities::DELIVERY_TIME,
                'oldIdentifier' => $name,
                'entityUuid' => $deliveryTimeUuid,
                'entityValue' => null,
                'checksum' => null,
                'additionalData' => null,
            ]
        );

        return $deliveryTimeUuid;
    }

    public function getCountryUuid(string $oldIdentifier, string $iso, string $iso3, string $connectionId, Context $context): ?string
    {
        $countryMapping = $this->getMapping($connectionId, DefaultEntities::COUNTRY, $oldIdentifier, $context);

        if ($countryMapping !== null) {
            return $countryMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $iso));
        $criteria->addFilter(new EqualsFilter('iso3', $iso3));
        $criteria->setLimit(1);

        $result = $this->countryRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $element = $result->getEntities()->first();

            if ($element === null) {
                return null;
            }

            $countryUuid = $element->getId();

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::COUNTRY,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $countryUuid,
                    'entityValue' => null,
                    'checksum' => null,
                    'additionalData' => null,
                ]
            );

            return $countryUuid;
        }

        return null;
    }

    public function getCurrencyUuid(string $connectionId, string $oldIsoCode, Context $context): ?string
    {
        $currencyMapping = $this->getMapping($connectionId, DefaultEntities::CURRENCY, $oldIsoCode, $context);

        if ($currencyMapping !== null) {
            return $currencyMapping['entityUuid'];
        }

        $currencyUuid = $this->getCurrencyUuidWithoutMapping($connectionId, $oldIsoCode, $context);
        if ($currencyUuid !== null) {
            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::CURRENCY,
                    'oldIdentifier' => $oldIsoCode,
                    'entityUuid' => $currencyUuid,
                    'entityValue' => null,
                    'checksum' => null,
                    'additionalData' => null,
                ]
            );
        }

        return $currencyUuid;
    }

    public function getCurrencyUuidWithoutMapping(string $connectionId, string $oldIsoCode, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', $oldIsoCode));
        $criteria->setLimit(1);

        $result = $this->currencyRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $element = $result->getEntities()->first();

            if ($element === null) {
                return null;
            }

            return $element->getId();
        }

        return null;
    }

    public function getTaxUuid(string $connectionId, float $taxRate, Context $context): ?string
    {
        $taxMapping = $this->getMapping($connectionId, DefaultEntities::TAX, (string) $taxRate, $context);

        if ($taxMapping !== null) {
            return $taxMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxRate', $taxRate));
        $criteria->setLimit(1);

        $result = $this->taxRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $tax = $result->getEntities()->first();

            if ($tax === null) {
                return null;
            }

            $taxUuid = $tax->getId();

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::TAX,
                    'oldIdentifier' => (string) $taxRate,
                    'entityUuid' => $taxUuid,
                    'entityValue' => null,
                    'checksum' => null,
                    'additionalData' => null,
                ]
            );

            return $taxUuid;
        }

        return null;
    }

    public function getNumberRangeUuid(string $type, string $oldIdentifier, string $checksum, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $connectionId = $connection->getId();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter(
            'number_range.type.technicalName',
            $type
        ));

        $result = $this->numberRangeRepo->searchIds($criteria, $context);

        if ($result->getTotal() > 0) {
            $numberRangeId = $result->firstId();

            if ($numberRangeId === null) {
                return null;
            }

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::NUMBER_RANGE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $numberRangeId,
                    'checksum' => $checksum,
                    'entityValue' => null,
                    'additionalData' => null,
                ]
            );

            return $numberRangeId;
        }

        return null;
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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', $entityName));

        $result = $this->mediaDefaultFolderRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $mediaDefaultFolder = $result->getEntities()->first();
            if ($mediaDefaultFolder === null) {
                return null;
            }

            $mediaDefaultFolder = $mediaDefaultFolder->getFolder();
            if ($mediaDefaultFolder === null) {
                return null;
            }

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::MEDIA_DEFAULT_FOLDER,
                    'oldIdentifier' => $entityName,
                    'entityUuid' => $mediaDefaultFolder->getId(),
                    'entityValue' => null,
                    'checksum' => null,
                    'additionalData' => null,
                ]
            );

            return $mediaDefaultFolder->getId();
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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('width', $width));
        $criteria->addFilter(new EqualsFilter('height', $height));

        $result = $this->thumbnailSizeRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $thumbnailSize = $result->getEntities()->first();

            if ($thumbnailSize === null) {
                return null;
            }

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::MEDIA_THUMBNAIL_SIZE,
                    'oldIdentifier' => $sizeString,
                    'entityUuid' => $thumbnailSize->getId(),
                    'entityValue' => null,
                    'checksum' => null,
                    'additionalData' => null,
                ]
            );

            return $thumbnailSize->getId();
        }

        return null;
    }

    public function getMigratedSalesChannelUuids(string $connectionId, Context $context): array
    {
        if (isset($this->migratedSalesChannels[$connectionId])) {
            return $this->migratedSalesChannels[$connectionId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::SALES_CHANNEL));

        $result = $this->migrationMappingRepo->search($criteria, $context)->getEntities();

        $uuids = [];
        foreach ($result as $swagMigrationMappingEntity) {
            $uuid = $swagMigrationMappingEntity->getEntityUuid();

            if ($uuid === null) {
                continue;
            }

            $uuids[] = $uuid;
            $this->migratedSalesChannels[$connectionId][] = $uuid;
        }

        return $uuids;
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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));

        $result = $this->documentTypeRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $documentType = $result->getEntities()->first();

            if ($documentType === null) {
                return null;
            }

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connection->getId(),
                    'entity' => DefaultEntities::ORDER_DOCUMENT_TYPE,
                    'oldIdentifier' => $technicalName,
                    'entityUuid' => $documentType->getId(),
                    'entityValue' => null,
                    'checksum' => null,
                    'additionalData' => null,
                ]
            );

            return $documentType->getId();
        }

        return null;
    }

    public function getLowestRootCategoryUuid(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', null));

        $result = $this->categoryRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $collection = $result->getEntities();
            $last = $collection->sortByPosition()->last();

            if ($last !== null) {
                return $last->getId();
            }
        }

        return null;
    }

    protected function saveMapping(array $mapping): void
    {
        $entity = $mapping['entity'];
        $oldIdentifier = $mapping['oldIdentifier'];
        $this->mappings[\md5($entity . $oldIdentifier)] = $mapping;
        $this->writeArray[] = $mapping;
    }

    protected function saveListMapping(array $mapping): void
    {
        $entity = $mapping['entity'];
        $oldIdentifier = $mapping['oldIdentifier'];
        $this->mappings[\md5($entity . $oldIdentifier)][] = $mapping;
        $this->writeArray[] = $mapping;
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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $id));
        $criteria->addFilter(new EqualsFilter('entityUuid', $uuid));

        $result = $this->migrationMappingRepo->searchIds($criteria, $context);

        if ($result->getTotal() > 0) {
            return true;
        }

        return false;
    }

    private function searchLanguageInMapping(string $localeCode, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::LANGUAGE));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $localeCode));
        $criteria->setLimit(1);

        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            return $result->getEntities()->first()?->getEntityUuid();
        }

        return null;
    }

    /**
     * @throws LocaleNotFoundException
     */
    private function searchLocale(string $localeCode, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', $localeCode));
        $criteria->setLimit(1);

        $result = $this->localeRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $element = $result->getEntities()->first();

            if ($element === null) {
                throw MigrationException::localeNotFound($localeCode);
            }

            return $element->getId();
        }

        throw MigrationException::localeNotFound($localeCode);
    }

    private function searchLanguageByLocale(string $localeUuid, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('localeId', $localeUuid));
        $criteria->setLimit(1);

        $result = $this->languageRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            return $result->getEntities()->first()?->getId();
        }

        return null;
    }

    private function writePerEntry(Context $context): void
    {
        foreach ($this->writeArray as $mapping) {
            try {
                $this->entityWriter->upsert(
                    $this->mappingDefinition,
                    [$mapping],
                    WriteContext::createFromContext($context)
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    'SwagMigrationAssistant: Error while writing migration mapping',
                    [
                        'error' => $e->getMessage(),
                        'mapping' => $mapping,
                    ]
                );
            }
        }
    }
}
