<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderEntity;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\NumberRange\NumberRangeEntity;
use Shopware\Core\System\Tax\TaxEntity;
use SwagMigrationNext\Exception\LocaleNotFoundException;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;
use SwagMigrationNext\Migration\MigrationContextInterface;

class MappingService implements MappingServiceInterface
{
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
    protected $salesChannelRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $salesChannelTypeRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $paymentRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $shippingMethodRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $taxRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $numberRangeRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $mediaDefaultFolderRepo;

    protected $uuids = [];

    protected $uuidLists = [];

    protected $migratedSalesChannels = [];

    protected $writeArray = [];

    protected $languageData = [];

    protected $locales = [];

    /**
     * @var LanguageEntity
     */
    protected $defaultLanguageData;

    /**
     * @var EntityRepositoryInterface
     */
    private $ruleRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $thumbnailSizeRepo;

    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    /**
     * @var string
     */
    private $defaultAvailabilityRule;

    /**
     * @var EntityDefinition
     */
    private $mappingDefinition;

    public function __construct(
        EntityRepositoryInterface $migrationMappingRepo,
        EntityRepositoryInterface $localeRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $salesChannelRepo,
        EntityRepositoryInterface $salesChannelTypeRepo,
        EntityRepositoryInterface $paymentRepository,
        EntityRepositoryInterface $shippingMethodRepo,
        EntityRepositoryInterface $taxRepo,
        EntityRepositoryInterface $numberRangeRepo,
        EntityRepositoryInterface $ruleRepo,
        EntityRepositoryInterface $thumbnailSizeRepo,
        EntityRepositoryInterface $mediaDefaultRepo,
        EntityWriterInterface $entityWriter,
        EntityDefinition $mappingDefinition
    ) {
        $this->migrationMappingRepo = $migrationMappingRepo;
        $this->localeRepository = $localeRepository;
        $this->languageRepository = $languageRepository;
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
        $this->salesChannelRepo = $salesChannelRepo;
        $this->salesChannelTypeRepo = $salesChannelTypeRepo;
        $this->paymentRepository = $paymentRepository;
        $this->shippingMethodRepo = $shippingMethodRepo;
        $this->taxRepo = $taxRepo;
        $this->numberRangeRepo = $numberRangeRepo;
        $this->ruleRepo = $ruleRepo;
        $this->thumbnailSizeRepo = $thumbnailSizeRepo;
        $this->mediaDefaultFolderRepo = $mediaDefaultRepo;
        $this->entityWriter = $entityWriter;
        $this->mappingDefinition = $mappingDefinition;
    }

    public function getUuid(string $connectionId, string $entityName, string $oldId, Context $context): ?string
    {
        if (isset($this->uuids[$entityName][$oldId])) {
            return $this->uuids[$entityName][$oldId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $oldId));
        $criteria->setLimit(1);
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingEntity $element */
            $element = $result->getEntities()->first();
            $uuid = $element->getEntityUuid();

            $this->uuids[$entityName][$oldId] = $uuid;

            return $uuid;
        }

        return null;
    }

    public function createNewUuidListItem(
        string $connectionId,
        string $entityName,
        string $oldId,
        ?array $additionalData = null,
        ?string $newUuid = null
    ): string {
        $uuid = Uuid::randomHex();
        if ($newUuid !== null) {
            $uuid = $newUuid;

            if ($this->isUuidDuplicate($connectionId, $entityName, $oldId, $newUuid)) {
                return $newUuid;
            }
        }

        $this->saveListMapping(
            [
                'connectionId' => $connectionId,
                'entity' => $entityName,
                'oldIdentifier' => $oldId,
                'entityUuid' => $uuid,
                'additionalData' => $additionalData,
            ]
        );

        return $uuid;
    }

    public function isUuidDuplicate(string $connectionId, string $entityName, string $id, string $uuid): bool
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

    public function getUuidList(string $connectionId, string $entityName, string $identifier, Context $context): array
    {
        if (isset($this->uuidLists[$entityName][$identifier])) {
            return $this->uuidLists[$entityName][$identifier];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $identifier));
        $result = $this->migrationMappingRepo->search($criteria, $context);

        $uuidList = [];
        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingEntity $entity */
            foreach ($result->getEntities() as $entity) {
                $uuidList[] = $entity->getEntityUuid();
            }
        }

        $this->uuidLists[$entityName][$identifier] = $uuidList;

        return $uuidList;
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

        $this->saveMapping(
            [
                'connectionId' => $connectionId,
                'entity' => $entityName,
                'oldIdentifier' => $oldId,
                'entityUuid' => $uuid,
                'additionalData' => $additionalData,
            ]
        );

        return $uuid;
    }

    public function getLanguageUuid(string $connectionId, string $localeCode, Context $context): ?string
    {
        if (isset($this->languageData[$localeCode])) {
            return $this->languageData[$localeCode];
        }

        $languageUuid = $this->searchLanguageInMapping($localeCode, $context);
        if ($languageUuid !== null) {
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

        $localeUuid = $this->getUuid($connectionId, DefaultEntities::LOCALE, $localeCode, $context);

        if ($localeUuid !== null) {
            $this->locales[$localeCode] = $localeUuid;

            return $localeUuid;
        }

        $localeUuid = $this->searchLocale($localeCode, $context);
        $this->locales[$localeCode] = $localeUuid;

        return $localeUuid;
    }

    public function getDefaultLanguage(Context $context): LanguageEntity
    {
        if (!empty($this->defaultLanguageData)) {
            return $this->defaultLanguageData;
        }

        $languageUuid = $context->getLanguageId();
        /** @var LanguageEntity $language */
        $language = $this->languageRepository->search(new Criteria([$languageUuid]), $context)->first();

        $this->defaultLanguageData = $language;

        return $language;
    }

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $connectionId, Context $context): ?string
    {
        $countryUuid = $this->getUuid($connectionId, DefaultEntities::COUNTRY, $oldId, $context);

        if ($countryUuid !== null) {
            return $countryUuid;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $iso));
        $criteria->addFilter(new EqualsFilter('iso3', $iso3));
        $criteria->setLimit(1);
        $result = $this->countryRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var CountryEntity $element */
            $element = $result->getEntities()->first();

            $countryUuid = $element->getId();

            $this->saveMapping(
                [
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::COUNTRY,
                    'oldIdentifier' => $oldId,
                    'entityUuid' => $countryUuid,
                ]
            );

            return $countryUuid;
        }

        return null;
    }

    public function getCurrencyUuid(string $connectionId, string $oldShortName, Context $context): ?string
    {
        $currencyUuid = $this->getUuid($connectionId, DefaultEntities::CURRENCY, $oldShortName, $context);

        if ($currencyUuid !== null) {
            return $currencyUuid;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortName', $oldShortName));
        $criteria->setLimit(1);
        $result = $this->currencyRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var CurrencyEntity $element */
            $element = $result->getEntities()->first();
            $currencyUuid = $element->getId();

            $this->saveMapping(
                [
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::CURRENCY,
                    'oldIdentifier' => $oldShortName,
                    'entityUuid' => $currencyUuid,
                ]
            );

            return $currencyUuid;
        }

        return null;
    }

    public function getTaxUuid(string $connectionId, float $taxRate, Context $context): ?string
    {
        $taxUuid = $this->getUuid($connectionId, DefaultEntities::TAX, (string) $taxRate, $context);

        if ($taxUuid !== null) {
            return $taxUuid;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxRate', $taxRate));
        $criteria->setLimit(1);
        $result = $this->taxRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var TaxEntity $tax */
            $tax = $result->getEntities()->first();
            $taxUuid = $tax->getId();

            $this->saveMapping(
                [
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

    public function getNumberRangeUuid(string $type, string $oldId, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter(
            'number_range.type.technicalName',
            $type
        ));

        $result = $this->numberRangeRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var NumberRangeEntity $numberRange */
            $numberRange = $result->getEntities()->first();

            $this->saveMapping(
                [
                    'connectionId' => $migrationContext->getConnection()->getId(),
                    'entity' => DefaultEntities::NUMBER_RANGE,
                    'oldIdentifier' => $oldId,
                    'entityUuid' => $numberRange->getId(),
                ]
            );

            return $numberRange->getId();
        }

        return null;
    }

    public function getDefaultFolderIdByEntity(string $entityName, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connectionId = $migrationContext->getConnection()->getId();
        $defaultFolderUuid = $this->getUuid($connectionId, DefaultEntities::MEDIA_DEFAULT_FOLDER, $entityName, $context);

        if ($defaultFolderUuid !== null) {
            return $defaultFolderUuid;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $result = $this->mediaDefaultFolderRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var MediaDefaultFolderEntity $mediaDefaultFolder */
            $mediaDefaultFolder = $result->getEntities()->first();

            $this->saveMapping(
                [
                    'connectionId' => $migrationContext->getConnection()->getId(),
                    'entity' => DefaultEntities::MEDIA_DEFAULT_FOLDER,
                    'oldIdentifier' => $entityName,
                    'entityUuid' => $mediaDefaultFolder->getFolder()->getId(),
                ]
            );

            return $mediaDefaultFolder->getFolder()->getId();
        }

        return null;
    }

    public function getThumbnailSizeUuid(int $width, int $height, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $sizeString = $width . '-' . $height;
        $connectionId = $migrationContext->getConnection()->getId();
        $thumbnailSizeUuid = $this->getUuid($connectionId, DefaultEntities::MEDIA_THUMBNAIL_SIZE, $sizeString, $context);

        if ($thumbnailSizeUuid !== null) {
            return $thumbnailSizeUuid;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('width', $width));
        $criteria->addFilter(new EqualsFilter('height', $height));
        $result = $this->thumbnailSizeRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var MediaThumbnailSizeEntity $thumbnailSize */
            $thumbnailSize = $result->getEntities()->first();

            $this->saveMapping(
                [
                    'connectionId' => $migrationContext->getConnection()->getId(),
                    'entity' => DefaultEntities::MEDIA_THUMBNAIL_SIZE,
                    'oldIdentifier' => $sizeString,
                    'entityUuid' => $thumbnailSize->getId(),
                ]
            );

            return $thumbnailSize->getId();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigratedSalesChannelUuids(string $connectionId, Context $context): array
    {
        if (isset($this->migratedSalesChannels[$connectionId])) {
            return $this->migratedSalesChannels[$connectionId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::SALES_CHANNEL));

        $result = $this->migrationMappingRepo->search($criteria, $context);
        /** @var SwagMigrationMappingCollection $saleschannelMappingCollection */
        $saleschannelMappingCollection = $result->getEntities();

        $uuids = [];
        foreach ($saleschannelMappingCollection as $swagMigrationMappingEntity) {
            $uuid = $swagMigrationMappingEntity->getEntityUuid();
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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'Cart >= 0'));
        /** @var RuleEntity $result */
        $result = $this->ruleRepo->search($criteria, $context)->first();

        $uuids = null;
        if ($result !== null) {
            $uuids = $result->getId();
        }

        return $uuids;
    }

    public function deleteMapping(string $entityUuid, string $connectionId, Context $context): void
    {
        foreach ($this->writeArray as $key => $writeMapping) {
            if ($writeMapping['connectionId'] === $connectionId && $writeMapping['entityUuid'] === $entityUuid) {
                unset($this->writeArray[$key]);
                break;
            }
        }

        if (!empty($this->uuids)) {
            foreach ($this->uuids as $entityName => $entityArray) {
                foreach ($entityArray as $oldId => $uuid) {
                    if ($uuid === $entityUuid) {
                        unset($this->uuids[$entityName][$oldId]);
                        break;
                    }
                }
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entityUuid', $entityUuid));
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->setLimit(1);
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingEntity $element */
            $element = $result->getEntities()->first();

            $this->migrationMappingRepo->delete([['id' => $element->getId()]], $context);
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

        $this->entityWriter->insert(
            $this->mappingDefinition,
            $this->writeArray,
            WriteContext::createFromContext($context)
        );

        $this->writeArray = [];
        $this->uuids = [];
    }

    public function pushMapping(string $connectionId, string $entity, string $oldIdentifier, string $uuid)
    {
        $this->saveMapping([
            'connectionId' => $connectionId,
            'entity' => $entity,
            'oldIdentifier' => $oldIdentifier,
            'entityUuid' => $uuid,
        ]);
    }

    protected function saveMapping(array $mapping): void
    {
        $entity = $mapping['entity'];
        $oldId = $mapping['oldIdentifier'];
        $uuid = $mapping['entityUuid'];

        $this->uuids[$entity][$oldId] = $uuid;
        $this->writeArray[] = $mapping;
    }

    protected function saveListMapping(array $mapping): void
    {
        $entity = $mapping['entity'];
        $oldId = $mapping['oldIdentifier'];
        $uuid = $mapping['entityUuid'];

        $this->uuids[$entity][$oldId][] = $uuid;
        $this->writeArray[] = $mapping;
    }

    private function searchLanguageInMapping(string $localeCode, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::LANGUAGE));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $localeCode));
        $criteria->setLimit(1);
        $result = $this->migrationMappingRepo->search($criteria, $context);

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
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', $localeCode));
        $criteria->setLimit(1);
        $result = $this->localeRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var LocaleEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        throw new LocaleNotFoundException($localeCode);
    }

    private function searchLanguageByLocale(string $localeUuid, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('localeId', $localeUuid));
        $criteria->setLimit(1);
        $result = $this->languageRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var LanguageEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }
}
