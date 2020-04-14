<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Cms\CmsPageDefinition;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderEntity;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\NumberRange\NumberRangeEntity;
use Shopware\Core\System\Tax\TaxEntity;
use SwagMigrationAssistant\Exception\LocaleNotFoundException;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class MappingService implements MappingServiceInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $mediaDefaultFolderRepo;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var array
     */
    protected $migratedSalesChannels = [];

    /**
     * @var array
     */
    protected $writeArray = [];

    /**
     * @var array
     */
    protected $languageData = [];

    /**
     * @var array
     */
    protected $locales = [];

    /**
     * @var array
     */
    protected $mappings = [];

    /**
     * @var EntityRepositoryInterface
     */
    protected $migrationMappingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $localeRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $languageRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $countryRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $currencyRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $taxRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $numberRangeRepo;

    /**
     * @var LanguageEntity
     */
    protected $defaultLanguageData;

    /**
     * @var EntityRepositoryInterface
     */
    protected $ruleRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $thumbnailSizeRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $categoryRepo;

    /**
     * @var EntityWriterInterface
     */
    protected $entityWriter;

    /**
     * @var string
     */
    protected $defaultAvailabilityRule;

    /**
     * @var EntityDefinition
     */
    protected $mappingDefinition;

    /**
     * @var EntityRepositoryInterface
     */
    protected $cmsPageRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $deliveryTimeRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $documentTypeRepo;

    public function __construct(
        EntityRepositoryInterface $migrationMappingRepo,
        EntityRepositoryInterface $localeRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $taxRepo,
        EntityRepositoryInterface $numberRangeRepo,
        EntityRepositoryInterface $ruleRepo,
        EntityRepositoryInterface $thumbnailSizeRepo,
        EntityRepositoryInterface $mediaDefaultRepo,
        EntityRepositoryInterface $categoryRepo,
        EntityRepositoryInterface $cmsPageRepo,
        EntityRepositoryInterface $deliveryTimeRepo,
        EntityRepositoryInterface $documentTypeRepo,
        EntityWriterInterface $entityWriter,
        EntityDefinition $mappingDefinition
    ) {
        $this->migrationMappingRepo = $migrationMappingRepo;
        $this->localeRepository = $localeRepository;
        $this->languageRepository = $languageRepository;
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
        $this->taxRepo = $taxRepo;
        $this->numberRangeRepo = $numberRangeRepo;
        $this->ruleRepo = $ruleRepo;
        $this->thumbnailSizeRepo = $thumbnailSizeRepo;
        $this->mediaDefaultFolderRepo = $mediaDefaultRepo;
        $this->categoryRepo = $categoryRepo;
        $this->cmsPageRepo = $cmsPageRepo;
        $this->deliveryTimeRepo = $deliveryTimeRepo;
        $this->documentTypeRepo = $documentTypeRepo;
        $this->entityWriter = $entityWriter;
        $this->mappingDefinition = $mappingDefinition;
    }

    public function getOrCreateMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
        ?string $checksum = null,
        ?array $additionalData = null,
        ?string $uuid = null
    ): array {
        $mapping = $this->getMapping($connectionId, $entityName, $oldIdentifier, $context);

        if (!isset($mapping)) {
            return $this->createMapping($connectionId, $entityName, $oldIdentifier, $checksum, $additionalData, $uuid);
        }

        if ($uuid !== null) {
            $mapping['entityUuid'] = $uuid;
            $this->saveMapping($mapping);

            return $mapping;
        }

        return $mapping;
    }

    public function getMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context
    ): ?array {
        if (isset($this->mappings[md5($entityName . $oldIdentifier)])) {
            return $this->mappings[md5($entityName . $oldIdentifier)];
        }
        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($connectionId, $entityName, $oldIdentifier) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
            $criteria->addFilter(new EqualsFilter('entity', $entityName));
            $criteria->addFilter(new EqualsFilter('oldIdentifier', $oldIdentifier));
            $criteria->setLimit(1);

            return $this->migrationMappingRepo->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingEntity $element */
            $element = $result->getEntities()->first();

            $mapping = [
                'id' => $element->getId(),
                'connectionId' => $element->getConnectionId(),
                'entity' => $element->getEntity(),
                'oldIdentifier' => $element->getOldIdentifier(),
                'entityUuid' => $element->getEntityUuid(),
                'checksum' => $element->getChecksum(),
                'additionalData' => $element->getAdditionalData(),
            ];
            $this->mappings[md5($entityName . $oldIdentifier)] = $mapping;

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
        ?string $uuid = null
    ): array {
        $mapping = [
            'id' => Uuid::randomHex(),
            'connectionId' => $connectionId,
            'entity' => $entityName,
            'oldIdentifier' => $oldIdentifier,
            'entityUuid' => $uuid ?? Uuid::randomHex(),
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

        $mapping = array_merge($mapping, $updateData);
        $this->saveMapping($mapping);

        return $mapping;
    }

    public function getMappings(string $connectionId, string $entityName, array $ids, Context $context): EntitySearchResult
    {
        return $context->disableCache(function (Context $context) use ($connectionId, $entityName, $ids) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
            $criteria->addFilter(new EqualsFilter('entity', $entityName));
            $criteria->addFilter(new EqualsAnyFilter('oldIdentifier', $ids));

            return $this->migrationMappingRepo->search($criteria, $context);
        });
    }

    public function preloadMappings(array $mappingIds, Context $context): void
    {
        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($mappingIds) {
            return $this->migrationMappingRepo->search(new Criteria($mappingIds), $context);
        });

        if ($result->count() > 0) {
            $elements = $result->getEntities()->getElements();
            /** @var SwagMigrationMappingEntity $mapping */
            foreach ($elements as $mapping) {
                $entityName = $mapping->getEntity();
                $oldIdentifier = $mapping->getOldIdentifier();
                $this->mappings[md5($entityName . $oldIdentifier)] = [
                    'id' => $mapping->getId(),
                    'connectionId' => $mapping->getConnectionId(),
                    'entity' => $entityName,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $mapping->getEntityUuid(),
                    'checksum' => $mapping->getChecksum(),
                    'additionalData' => $mapping->getAdditionalData(),
                ];
            }
            unset($result);
        }
    }

    public function getUuidsByEntity(string $connectionId, string $entityName, Context $context): array
    {
        /** @var SwagMigrationMappingEntity[] $entities */
        $entities = $context->disableCache(function (Context $context) use ($connectionId, $entityName) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
            $criteria->addFilter(new EqualsFilter('entity', $entityName));

            return $this->migrationMappingRepo->search($criteria, $context)->getEntities();
        });

        $entityUuids = [];
        foreach ($entities as $entity) {
            $entityUuids[] = $entity->getEntityUuid();
        }

        return $entityUuids;
    }

    public function getValue(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?string
    {
        if (isset($this->values[$entityName][$oldIdentifier])) {
            return $this->values[$entityName][$oldIdentifier];
        }

        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($connectionId, $entityName, $oldIdentifier) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
            $criteria->addFilter(new EqualsFilter('entity', $entityName));
            $criteria->addFilter(new EqualsFilter('oldIdentifier', $oldIdentifier));
            $criteria->setLimit(1);

            return $this->migrationMappingRepo->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingEntity $element */
            $element = $result->getEntities()->first();
            $value = $element->getEntityValue();

            $this->values[$entityName][$oldIdentifier] = $value;

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
                'additionalData' => $additionalData,
            ]
        );
    }

    public function getUuidList(string $connectionId, string $entityName, string $identifier, Context $context): array
    {
        if (isset($this->mappings[md5($entityName . $identifier)])) {
            return $this->mappings[md5($entityName . $identifier)];
        }

        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($connectionId, $entityName, $identifier) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
            $criteria->addFilter(new EqualsFilter('entity', $entityName));
            $criteria->addFilter(new EqualsFilter('oldIdentifier', $identifier));

            return $this->migrationMappingRepo->search($criteria, $context);
        });

        $uuidList = [];
        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingEntity $entity */
            foreach ($result->getEntities() as $entity) {
                $uuidList[] = $entity->getEntityUuid();
            }
        }

        $this->mappings[md5($entityName . $identifier)] = $uuidList;

        return $uuidList;
    }

    public function deleteMapping(string $entityUuid, string $connectionId, Context $context): void
    {
        foreach ($this->writeArray as $key => $writeMapping) {
            if ($writeMapping['connectionId'] === $connectionId && $writeMapping['entityUuid'] === $entityUuid) {
                unset($this->writeArray[$key]);
                $this->writeArray = array_values($this->writeArray);
                break;
            }
        }

        foreach ($this->mappings as $hash => $mapping) {
            if ($mapping['entityUuid'] === $entityUuid) {
                unset($this->mappings[$hash]);
            }
        }

        /** @var IdSearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($entityUuid, $connectionId) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('entityUuid', $entityUuid));
            $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
            $criteria->setLimit(1);

            return $this->migrationMappingRepo->searchIds($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            $this->migrationMappingRepo->delete(array_values($result->getData()), $context);
        }
    }

    public function bulkDeleteMapping(array $mappingUuids, Context $context): void
    {
        if (!empty($mappingUuids)) {
            $deleteArray = [];
            foreach ($mappingUuids as $uuid) {
                $deleteArray[] = [
                    'id' => $uuid,
                ];
            }

            $this->migrationMappingRepo->delete($deleteArray, $context);
        }
    }

    public function writeMapping(Context $context): void
    {
        if (empty($this->writeArray)) {
            return;
        }

        $this->entityWriter->upsert(
            $this->mappingDefinition,
            $this->writeArray,
            WriteContext::createFromContext($context)
        );

        $this->writeArray = [];
        $this->mappings = [];
    }

    public function pushMapping(string $connectionId, string $entity, string $oldIdentifier, string $uuid): void
    {
        $this->saveMapping([
            'connectionId' => $connectionId,
            'entity' => $entity,
            'oldIdentifier' => $oldIdentifier,
            'entityUuid' => $uuid,
        ]);
    }

    public function pushValueMapping(string $connectionId, string $entity, string $oldIdentifier, string $value): void
    {
        $this->saveMapping([
            'connectionId' => $connectionId,
            'entity' => $entity,
            'oldIdentifier' => $oldIdentifier,
            'entityValue' => $value,
        ]);
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

        /** @var CmsPageEntity|null $cmsPage */
        $cmsPage = $this->cmsPageRepo->search($criteria, $context)->first();

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

            return $localeMapping['entityUuid'];
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

        $language = $context->disableCache(function (Context $context) use ($languageUuid): ?LanguageEntity {
            $criteria = new Criteria([$languageUuid]);
            $criteria->addAssociation('locale');

            return $this->languageRepository->search($criteria, $context)->first();
        });

        if ($language === null) {
            return null;
        }

        $this->defaultLanguageData = $language;

        return $language;
    }

    public function getDeliveryTime(string $connectionId, Context $context, int $minValue, int $maxValue, string $unit, string $name): string
    {
        $deliveryTimeMapping = $this->getMapping($connectionId, DefaultEntities::DELIVERY_TIME, $name, $context);

        if ($deliveryTimeMapping !== null) {
            return $deliveryTimeMapping['entityUuid'];
        }

        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($minValue, $maxValue, $unit) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('min', $minValue));
            $criteria->addFilter(new EqualsFilter('max', $maxValue));
            $criteria->addFilter(new EqualsFilter('unit', $unit));
            $criteria->setLimit(1);

            return $this->deliveryTimeRepo->search($criteria, $context);
        });

        $deliveryTimeUuid = Uuid::randomHex();
        if ($result->getTotal() > 0) {
            /** @var DeliveryTimeEntity $element */
            $element = $result->getEntities()->first();

            $deliveryTimeUuid = $element->getId();
        }

        $this->saveMapping(
            [
                'id' => Uuid::randomHex(),
                'connectionId' => $connectionId,
                'entity' => DefaultEntities::DELIVERY_TIME,
                'oldIdentifier' => $name,
                'entityUuid' => $deliveryTimeUuid,
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

        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($iso, $iso3) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('iso', $iso));
            $criteria->addFilter(new EqualsFilter('iso3', $iso3));
            $criteria->setLimit(1);

            return $this->countryRepository->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var CountryEntity $element */
            $element = $result->getEntities()->first();
            $countryUuid = $element->getId();

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::COUNTRY,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $countryUuid,
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
                ]
            );
        }

        return $currencyUuid;
    }

    public function getCurrencyUuidWithoutMapping(string $connectionId, string $oldIsoCode, Context $context): ?string
    {
        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($oldIsoCode) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('isoCode', $oldIsoCode));
            $criteria->setLimit(1);

            return $this->currencyRepository->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var CurrencyEntity $element */
            $element = $result->getEntities()->first();

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

        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($taxRate) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('taxRate', $taxRate));
            $criteria->setLimit(1);

            return $this->taxRepo->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var TaxEntity $tax */
            $tax = $result->getEntities()->first();
            $taxUuid = $tax->getId();

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::TAX,
                    'oldIdentifier' => (string) $taxRate,
                    'entityUuid' => $taxUuid,
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
        $result = $context->disableCache(function (Context $context) use ($type): EntitySearchResult {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter(
                'number_range.type.technicalName',
                $type
            ));

            return $this->numberRangeRepo->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var NumberRangeEntity|null $numberRange */
            $numberRange = $result->getEntities()->first();

            if ($numberRange === null) {
                return null;
            }

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::NUMBER_RANGE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $numberRange->getId(),
                    'checksum' => $checksum,
                ]
            );

            return $numberRange->getId();
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

        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($entityName) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('entity', $entityName));

            return $this->mediaDefaultFolderRepo->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var MediaDefaultFolderEntity|null $mediaDefaultFolder */
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

        $result = $context->disableCache(function (Context $context) use ($width, $height): EntitySearchResult {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('width', $width));
            $criteria->addFilter(new EqualsFilter('height', $height));

            return $this->thumbnailSizeRepo->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var MediaThumbnailSizeEntity $thumbnailSize */
            $thumbnailSize = $result->getEntities()->first();

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::MEDIA_THUMBNAIL_SIZE,
                    'oldIdentifier' => $sizeString,
                    'entityUuid' => $thumbnailSize->getId(),
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

        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($connectionId) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
            $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::SALES_CHANNEL));

            return $this->migrationMappingRepo->search($criteria, $context);
        });

        /** @var SwagMigrationMappingCollection $saleschannelMappingCollection */
        $saleschannelMappingCollection = $result->getEntities();

        $uuids = [];
        foreach ($saleschannelMappingCollection as $swagMigrationMappingEntity) {
            $uuid = $swagMigrationMappingEntity->getEntityUuid();

            if ($uuid === null) {
                continue;
            }

            $uuids[] = $uuid;
            $this->migratedSalesChannels[$connectionId][] = $uuid;
        }

        return $uuids;
    }

    //Todo: Remove if we migrate every data of the shipping method
    public function getDefaultAvailabilityRule(Context $context): ?string
    {
        if (isset($this->defaultAvailabilityRule)) {
            return $this->defaultAvailabilityRule;
        }

        $result = $context->disableCache(function (Context $context): ?RuleEntity {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', 'Cart >= 0'));

            return $this->ruleRepo->search($criteria, $context)->first();
        });

        $uuids = null;
        if ($result !== null) {
            $uuids = $result->getId();
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

        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($technicalName) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));

            return $this->documentTypeRepo->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var DocumentTypeEntity $documentType */
            $documentType = $result->getEntities()->first();

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connection->getId(),
                    'entity' => DefaultEntities::ORDER_DOCUMENT_TYPE,
                    'oldIdentifier' => $technicalName,
                    'entityUuid' => $documentType->getId(),
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
            /** @var CategoryCollection $collection */
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
        $this->mappings[md5($entity . $oldIdentifier)] = $mapping;
        $this->writeArray[] = $mapping;
    }

    protected function saveListMapping(array $mapping): void
    {
        $entity = $mapping['entity'];
        $oldIdentifier = $mapping['oldIdentifier'];
        $this->mappings[md5($entity . $oldIdentifier)][] = $mapping;
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

        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($connectionId, $entityName, $id, $uuid) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
            $criteria->addFilter(new EqualsFilter('entity', $entityName));
            $criteria->addFilter(new EqualsFilter('oldIdentifier', $id));
            $criteria->addFilter(new EqualsFilter('entityUuid', $uuid));

            return $this->migrationMappingRepo->searchIds($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            return true;
        }

        return false;
    }

    private function searchLanguageInMapping(string $localeCode, Context $context): ?string
    {
        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($localeCode) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::LANGUAGE));
            $criteria->addFilter(new EqualsFilter('oldIdentifier', $localeCode));
            $criteria->setLimit(1);

            return $this->migrationMappingRepo->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingEntity $element */
            $element = $result->getEntities()->first();

            return $element->getEntityUuid();
        }

        return null;
    }

    /**
     * @throws LocaleNotFoundException
     */
    private function searchLocale(string $localeCode, Context $context): string
    {
        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($localeCode) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('code', $localeCode));
            $criteria->setLimit(1);

            return $this->localeRepository->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var LocaleEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        throw new LocaleNotFoundException($localeCode);
    }

    private function searchLanguageByLocale(string $localeUuid, Context $context): ?string
    {
        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($localeUuid) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('localeId', $localeUuid));
            $criteria->setLimit(1);

            return $this->languageRepository->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var LanguageEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }
}
