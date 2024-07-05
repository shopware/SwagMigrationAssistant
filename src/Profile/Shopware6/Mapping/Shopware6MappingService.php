<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Mapping;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigCollection;
use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsPageTranslation\CmsPageTranslationEntity;
use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeCollection;
use Shopware\Core\Content\MailTemplate\MailTemplateCollection;
use Shopware\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderCollection;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeCollection;
use Shopware\Core\Content\Product\SalesChannel\Sorting\ProductSortingCollection;
use Shopware\Core\Content\Product\SalesChannel\Sorting\ProductSortingEntity;
use Shopware\Core\Content\Rule\RuleCollection;
use Shopware\Core\Content\Seo\SeoUrlTemplate\SeoUrlTemplateCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\DeliveryTime\DeliveryTimeCollection;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Locale\LocaleCollection;
use Shopware\Core\System\NumberRange\NumberRangeCollection;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateCollection;
use Shopware\Core\System\SystemConfig\SystemConfigCollection;
use Shopware\Core\System\Tax\Aggregate\TaxRule\TaxRuleCollection;
use Shopware\Core\System\Tax\Aggregate\TaxRuleType\TaxRuleTypeCollection;
use Shopware\Core\System\Tax\TaxCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class Shopware6MappingService extends MappingService implements Shopware6MappingServiceInterface
{
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
     * @param EntityRepository<MediaDefaultFolderCollection> $mediaDefaultRepo
     * @param EntityRepository<CategoryCollection> $categoryRepo
     * @param EntityRepository<CmsPageCollection> $cmsPageRepo
     * @param EntityRepository<DeliveryTimeCollection> $deliveryTimeRepo
     * @param EntityRepository<DocumentTypeCollection> $documentTypeRepo
     * @param EntityRepository<NumberRangeCollection> $numberRangeTypeRepo
     * @param EntityRepository<MailTemplateTypeCollection> $mailTemplateTypeRepo
     * @param EntityRepository<MailTemplateCollection> $mailTemplateRepo
     * @param EntityRepository<SalutationCollection> $salutationRepo
     * @param EntityRepository<SeoUrlTemplateCollection> $seoUrlTemplateRepo
     * @param EntityRepository<SystemConfigCollection> $systemConfigRepo
     * @param EntityRepository<ProductSortingCollection> $productSortingRepo
     * @param EntityRepository<StateMachineStateCollection> $stateMachineStateRepo
     * @param EntityRepository<DocumentBaseConfigCollection> $documentBaseConfigRepo
     * @param EntityRepository<CountryStateCollection> $countryStateRepo
     * @param EntityRepository<TaxRuleCollection> $taxRuleRepo
     * @param EntityRepository<TaxRuleTypeCollection> $taxRuleTypeRepo
     */
    public function __construct(
        EntityRepository $migrationMappingRepo,
        EntityRepository $localeRepository,
        EntityRepository $languageRepository,
        EntityRepository $countryRepository,
        EntityRepository $currencyRepository,
        EntityRepository $taxRepo,
        EntityRepository $numberRangeRepo,
        EntityRepository $ruleRepo,
        EntityRepository $thumbnailSizeRepo,
        EntityRepository $mediaDefaultRepo,
        EntityRepository $categoryRepo,
        EntityRepository $cmsPageRepo,
        EntityRepository $deliveryTimeRepo,
        EntityRepository $documentTypeRepo,
        EntityWriterInterface $entityWriter,
        EntityDefinition $mappingDefinition,
        LoggerInterface $logger,
        private readonly EntityRepository $numberRangeTypeRepo,
        private readonly EntityRepository $mailTemplateTypeRepo,
        private readonly EntityRepository $mailTemplateRepo,
        private readonly EntityRepository $salutationRepo,
        private readonly EntityRepository $seoUrlTemplateRepo,
        private readonly EntityRepository $systemConfigRepo,
        private readonly EntityRepository $productSortingRepo,
        private readonly EntityRepository $stateMachineStateRepo,
        private readonly EntityRepository $documentBaseConfigRepo,
        private readonly EntityRepository $countryStateRepo,
        private readonly EntityRepository $taxRuleRepo,
        private readonly EntityRepository $taxRuleTypeRepo
    ) {
        parent::__construct(
            $migrationMappingRepo,
            $localeRepository,
            $languageRepository,
            $countryRepository,
            $currencyRepository,
            $taxRepo,
            $numberRangeRepo,
            $ruleRepo,
            $thumbnailSizeRepo,
            $mediaDefaultRepo,
            $categoryRepo,
            $cmsPageRepo,
            $deliveryTimeRepo,
            $documentTypeRepo,
            $entityWriter,
            $mappingDefinition,
            $logger
        );
    }

    public function getMailTemplateTypeUuid(string $type, string $oldIdentifier, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $connectionId = $connection->getId();
        $typeMapping = $this->getMapping($connectionId, DefaultEntities::MAIL_TEMPLATE_TYPE, $oldIdentifier, $context);

        if ($typeMapping !== null) {
            return $typeMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $type));

        $mailTemplateTypeId = $this->mailTemplateTypeRepo->searchIds($criteria, $context)->firstId();

        if ($mailTemplateTypeId !== null) {
            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::NUMBER_RANGE_TYPE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $mailTemplateTypeId,
                ]
            );
        }

        return $mailTemplateTypeId;
    }

    public function getSystemDefaultMailTemplateUuid(string $type, string $oldIdentifier, string $connectionId, MigrationContextInterface $migrationContext, Context $context): string
    {
        $defaultMailTemplate = $this->getMapping($connectionId, DefaultEntities::MAIL_TEMPLATE, $oldIdentifier, $context);

        if ($defaultMailTemplate !== null) {
            return (string) $defaultMailTemplate['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('systemDefault', true));
        $criteria->addFilter(new EqualsFilter('mailTemplateTypeId', $type));
        $criteria->setLimit(1);

        $mailTemplateId = $this->mailTemplateRepo->searchIds($criteria, $context)->firstId();

        if ($mailTemplateId === null) {
            $mailTemplateId = $oldIdentifier;
        }

        $this->saveMapping(
            [
                'id' => Uuid::randomHex(),
                'connectionId' => $connectionId,
                'entity' => DefaultEntities::MAIL_TEMPLATE,
                'oldIdentifier' => $oldIdentifier,
                'entityUuid' => $mailTemplateId,
            ]
        );

        return $mailTemplateId;
    }

    public function getNumberRangeTypeUuid(string $type, string $oldIdentifier, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $connectionId = $connection->getId();
        $typeMapping = $this->getMapping($connectionId, DefaultEntities::NUMBER_RANGE_TYPE, $oldIdentifier, $context);

        if ($typeMapping !== null) {
            return $typeMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $type));

        $numberRangeTypeId = $this->numberRangeTypeRepo->searchIds($criteria, $context)->firstId();

        if ($numberRangeTypeId !== null) {
            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::NUMBER_RANGE_TYPE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $numberRangeTypeId,
                ]
            );
        }

        return $numberRangeTypeId;
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

        $mediaDefaultFolderId = $this->mediaDefaultFolderRepo->searchIds($criteria, $context)->firstId();

        if ($mediaDefaultFolderId !== null) {
            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::MEDIA_DEFAULT_FOLDER,
                    'oldIdentifier' => $entityName,
                    'entityUuid' => $mediaDefaultFolderId,
                ]
            );
        }

        return $mediaDefaultFolderId;
    }

    public function getSalutationUuid(string $oldIdentifier, string $salutationKey, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $connectionId = $connection->getId();
        $salutationMapping = $this->getMapping($connectionId, DefaultEntities::SALUTATION, $oldIdentifier, $context);

        if ($salutationMapping !== null) {
            return $salutationMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', $salutationKey));
        $criteria->setLimit(1);

        $salutationId = $this->salutationRepo->searchIds($criteria, $context)->firstId();

        if ($salutationId !== null) {
            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::SALUTATION,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $salutationId,
                ]
            );
        }

        return $salutationId;
    }

    public function getSeoUrlTemplateUuid(
        string $oldIdentifier,
        ?string $salesChannelId,
        string $routeName,
        MigrationContextInterface $migrationContext,
        Context $context
    ): ?string {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $connectionId = $connection->getId();
        $seoUrlTemplateMapping = $this->getMapping($connectionId, DefaultEntities::SEO_URL_TEMPLATE, $oldIdentifier, $context);
        if ($seoUrlTemplateMapping !== null) {
            return $seoUrlTemplateMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter('salesChannelId', $salesChannelId),
                    new EqualsFilter('routeName', $routeName),
                ]
            )
        );
        $criteria->setLimit(1);

        $seoUrlTemplateId = $this->seoUrlTemplateRepo->searchIds($criteria, $context)->firstId();

        if ($seoUrlTemplateId !== null) {
            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::SEO_URL_TEMPLATE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $seoUrlTemplateId,
                ]
            );
        }

        return $seoUrlTemplateId;
    }

    public function getSystemConfigUuid(string $oldIdentifier, string $configurationKey, ?string $salesChannelId, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $connectionId = $connection->getId();
        $systemConfigMapping = $this->getMapping($connectionId, DefaultEntities::SYSTEM_CONFIG, $oldIdentifier, $context);

        if ($systemConfigMapping !== null) {
            return $systemConfigMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter('salesChannelId', $salesChannelId),
                    new EqualsFilter('configurationKey', $configurationKey),
                ]
            )
        );
        $criteria->setLimit(1);

        $systemConfigId = $this->systemConfigRepo->searchIds($criteria, $context)->firstId();

        if ($systemConfigId !== null) {
            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::SYSTEM_CONFIG,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $systemConfigId,
                ]
            );
        }

        return $systemConfigId;
    }

    public function getProductSortingUuid(string $key, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('key', $key));
        $criteria->setLimit(1);

        $productSorting = $this->productSortingRepo->search($criteria, $context)->first();

        $id = null;
        $isLocked = false;

        if ($productSorting instanceof ProductSortingEntity) {
            $id = $productSorting->getId();
            $isLocked = $productSorting->isLocked();
        }

        return [$id, $isLocked];
    }

    public function getStateMachineStateUuid(string $oldIdentifier, string $technicalName, string $stateMachineTechnicalName, MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return null;
        }

        $connectionId = $connection->getId();
        $stateMachineStateMapping = $this->getMapping($connectionId, DefaultEntities::STATE_MACHINE_STATE, $oldIdentifier, $context);

        if ($stateMachineStateMapping !== null) {
            return $stateMachineStateMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        $criteria->addFilter(new EqualsFilter('stateMachine.technicalName', $stateMachineTechnicalName));
        $criteria->setLimit(1);

        $stateMachineStateId = $this->stateMachineStateRepo->searchIds($criteria, $context)->firstId();

        if ($stateMachineStateId !== null) {
            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::STATE_MACHINE_STATE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $stateMachineStateId,
                ]
            );
        }

        return $stateMachineStateId;
    }

    public function getGlobalDocumentBaseConfigUuid(string $oldIdentifier, string $documentTypeId, string $connectionId, MigrationContextInterface $migrationContext, Context $context): string
    {
        $globalDocumentBaseConfig = $this->getMapping($connectionId, DefaultEntities::ORDER_DOCUMENT_BASE_CONFIG, $oldIdentifier, $context);

        if ($globalDocumentBaseConfig !== null) {
            return (string) $globalDocumentBaseConfig['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('global', true));
        $criteria->addFilter(new EqualsFilter('documentTypeId', $documentTypeId));
        $criteria->setLimit(1);

        $baseConfigId = $this->documentBaseConfigRepo->searchIds($criteria, $context)->firstId();

        if ($baseConfigId === null) {
            $baseConfigId = $oldIdentifier;
        }

        $this->saveMapping(
            [
                'id' => Uuid::randomHex(),
                'connectionId' => $connectionId,
                'entity' => DefaultEntities::ORDER_DOCUMENT_BASE_CONFIG,
                'oldIdentifier' => $oldIdentifier,
                'entityUuid' => $baseConfigId,
            ]
        );

        return $baseConfigId;
    }

    public function getCmsPageUuidByNames(array $names, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addAssociation('translations');
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsAnyFilter('translations.name', $names),
            new EqualsAnyFilter('name', $names),
        ]));
        $criteria->addFilter(new EqualsFilter('locked', false));

        $cmsPages = $this->cmsPageRepo->search($criteria, $context)->getEntities();

        foreach ($cmsPages as $cmsPage) {
            $translations = $cmsPage->getTranslations();
            if ($translations === null) {
                continue;
            }

            $newNames = \array_map(static function (CmsPageTranslationEntity $translation) {
                return $translation->getName();
            }, $translations->getElements());

            if (\count(\array_diff($names, $newNames)) > 0 && \count($names) === \count($newNames)) {
                continue;
            }

            return $cmsPage->getId();
        }

        return null;
    }

    public function mapLockedCmsPageUuidByNameAndType(
        array $names,
        string $type,
        string $oldIdentifier,
        string $connectionId,
        MigrationContextInterface $migrationContext,
        Context $context
    ): void {
        $cmsPageMapping = $this->getMapping($connectionId, DefaultEntities::CMS_PAGE, $oldIdentifier, $context);

        if ($cmsPageMapping !== null) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addAssociation('translations');
        $criteria->addFilter(new EqualsAnyFilter('translations.name', $names));
        $criteria->addFilter(new EqualsFilter('type', $type));
        $criteria->addFilter(new EqualsFilter('locked', true));

        $cmsPages = $this->cmsPageRepo->search($criteria, $context)->getEntities();

        foreach ($cmsPages as $cmsPage) {
            $translations = $cmsPage->getTranslations();
            if ($translations === null) {
                continue;
            }

            $newNames = \array_map(static function (CmsPageTranslationEntity $translation) {
                return $translation->getName();
            }, $translations->getElements());

            if (\count(\array_diff($names, $newNames)) > 0 && \count($names) === \count($newNames)) {
                continue;
            }

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::CMS_PAGE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $cmsPage->getId(),
                ]
            );

            return;
        }
    }

    public function getCountryStateUuid(string $oldIdentifier, string $countryIso, string $countryIso3, string $countryStateCode, string $connectionId, Context $context): ?string
    {
        $countryStateMapping = $this->getMapping($connectionId, DefaultEntities::COUNTRY_STATE, $oldIdentifier, $context);

        if ($countryStateMapping !== null) {
            return $countryStateMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortCode', $countryStateCode));
        $criteria->addFilter(new EqualsFilter('country.iso', $countryIso));
        $criteria->addFilter(new EqualsFilter('country.iso3', $countryIso3));
        $criteria->setLimit(1);

        $countryStateUuid = $this->countryStateRepo->searchIds($criteria, $context)->firstId();

        if ($countryStateUuid !== null) {
            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::COUNTRY_STATE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $countryStateUuid,
                ]
            );
        }

        return $countryStateUuid;
    }

    public function getTaxUuidByCriteria(string $connectionId, string $sourceId, float $taxRate, string $name, Context $context): ?string
    {
        $taxMapping = $this->getMapping($connectionId, DefaultEntities::TAX, $sourceId, $context);

        if ($taxMapping !== null) {
            return $taxMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxRate', $taxRate));
        $criteria->addFilter(new EqualsFilter('name', $name));
        $criteria->setLimit(1);

        $result = $this->taxRepo->searchIds($criteria, $context);

        if ($result->getTotal() > 0) {
            $taxUuid = $result->firstId();

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::TAX,
                    'oldIdentifier' => $sourceId,
                    'entityUuid' => $taxUuid,
                ]
            );

            return $taxUuid;
        }

        return null;
    }

    public function getTaxRuleUuidByCriteria(string $connectionId, string $sourceId, string $taxId, string $countryId, string $taxRuleTypeId, Context $context): ?string
    {
        $taxRuleMapping = $this->getMapping($connectionId, DefaultEntities::TAX_RULE, $sourceId, $context);

        if ($taxRuleMapping !== null) {
            return $taxRuleMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxId', $taxId));
        $criteria->addFilter(new EqualsFilter('countryId', $countryId));
        $criteria->addFilter(new EqualsFilter('taxRuleTypeId', $taxRuleTypeId));
        $criteria->setLimit(1);

        $result = $this->taxRuleRepo->searchIds($criteria, $context);

        if ($result->getTotal() > 0) {
            $taxRuleUuid = $result->firstId();

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::TAX_RULE,
                    'oldIdentifier' => $sourceId,
                    'entityUuid' => $taxRuleUuid,
                ]
            );

            return $taxRuleUuid;
        }

        return null;
    }

    public function getTaxRuleTypeUuidByCriteria(string $connectionId, string $sourceId, string $technicalName, Context $context): ?string
    {
        $taxRuleTypeMapping = $this->getMapping($connectionId, DefaultEntities::TAX_RULE_TYPE, $sourceId, $context);

        if ($taxRuleTypeMapping !== null) {
            return $taxRuleTypeMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        $criteria->setLimit(1);

        $result = $this->taxRuleTypeRepo->searchIds($criteria, $context);

        if ($result->getTotal() > 0) {
            $taxRuleTypeUuid = $result->firstId();

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::TAX_RULE_TYPE,
                    'oldIdentifier' => $sourceId,
                    'entityUuid' => $taxRuleTypeUuid,
                ]
            );

            return $taxRuleTypeUuid;
        }

        return null;
    }
}
