<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Mapping;

use Shopware\Core\Content\Cms\Aggregate\CmsPageTranslation\CmsPageTranslationEntity;
use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Content\Product\SalesChannel\Sorting\ProductSortingEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class Shopware6MappingService extends MappingService implements Shopware6MappingServiceInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $numberRangeTypeRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $mailTemplateTypeRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $mailTemplateRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $salutationRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $seoUrlTemplateRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $systemConfigRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $productSortingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $stateMachineStateRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $countryStateRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $documentBaseConfigRepo;

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
        EntityDefinition $mappingDefinition,
        EntityRepositoryInterface $numberRangeTypeRepo,
        EntityRepositoryInterface $mailTemplateTypeRepo,
        EntityRepositoryInterface $mailTemplateRepo,
        EntityRepositoryInterface $salutationRepo,
        EntityRepositoryInterface $seoUrlTemplateRepo,
        EntityRepositoryInterface $systemConfigRepo,
        EntityRepositoryInterface $productSortingRepo,
        EntityRepositoryInterface $stateMachineStateRepo,
        EntityRepositoryInterface $documentBaseConfigRepo,
        EntityRepositoryInterface $countryStateRepo
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
            $mappingDefinition
        );

        $this->numberRangeTypeRepo = $numberRangeTypeRepo;
        $this->mailTemplateTypeRepo = $mailTemplateTypeRepo;
        $this->mailTemplateRepo = $mailTemplateRepo;
        $this->salutationRepo = $salutationRepo;
        $this->seoUrlTemplateRepo = $seoUrlTemplateRepo;
        $this->systemConfigRepo = $systemConfigRepo;
        $this->productSortingRepo = $productSortingRepo;
        $this->stateMachineStateRepo = $stateMachineStateRepo;
        $this->documentBaseConfigRepo = $documentBaseConfigRepo;
        $this->countryStateRepo = $countryStateRepo;
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

        /** @var string|null $mailTemplateTypeId */
        $mailTemplateTypeId = $context->disableCache(function (Context $context) use ($type): ?string {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('technicalName', $type));

            return $this->mailTemplateTypeRepo->searchIds($criteria, $context)->firstId();
        });

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
            return $defaultMailTemplate['entityUuid'];
        }

        /** @var string|null $mailTemplateId */
        $mailTemplateId = $context->disableCache(function (Context $context) use ($type) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('systemDefault', true));
            $criteria->addFilter(new EqualsFilter('mailTemplateTypeId', $type));
            $criteria->setLimit(1);

            return $this->mailTemplateRepo->searchIds($criteria, $context)->firstId();
        });

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

        /** @var string|null $numberRangeTypeId */
        $numberRangeTypeId = $context->disableCache(function (Context $context) use ($type): ?string {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('technicalName', $type));

            return $this->numberRangeTypeRepo->searchIds($criteria, $context)->firstId();
        });

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

        /** @var string|null $mediaDefaultFolderId */
        $mediaDefaultFolderId = $context->disableCache(function (Context $context) use ($entityName): ?string {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('entity', $entityName));

            return $this->mediaDefaultFolderRepo->searchIds($criteria, $context)->firstId();
        });

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

        /** @var string|null $salutationId */
        $salutationId = $context->disableCache(function (Context $context) use ($salutationKey): ?string {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('salutationKey', $salutationKey));
            $criteria->setLimit(1);

            return $this->salutationRepo->searchIds($criteria, $context)->firstId();
        });

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

        /** @var string|null $seoUrlTemplateId */
        $seoUrlTemplateId = $context->disableCache(function (Context $context) use ($salesChannelId, $routeName): ?string {
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

            return $this->seoUrlTemplateRepo->searchIds($criteria, $context)->firstId();
        });

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

        /** @var string|null $systemConfigId */
        $systemConfigId = $context->disableCache(function (Context $context) use ($configurationKey, $salesChannelId): ?string {
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

            return $this->systemConfigRepo->searchIds($criteria, $context)->firstId();
        });

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
        /** @var EntitySearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($key) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('key', $key));
            $criteria->setLimit(1);

            return $this->productSortingRepo->search($criteria, $context);
        });

        /** @var ProductSortingEntity|null $productSorting */
        $productSorting = $result->first();
        $id = null;
        $isLocked = false;

        if ($productSorting !== null) {
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

        /** @var string|null $stateMachineStateId */
        $stateMachineStateId = $context->disableCache(function (Context $context) use ($technicalName, $stateMachineTechnicalName): ?string {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
            $criteria->addFilter(new EqualsFilter('stateMachine.technicalName', $stateMachineTechnicalName));
            $criteria->setLimit(1);

            return $this->stateMachineStateRepo->searchIds($criteria, $context)->firstId();
        });

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
            return $globalDocumentBaseConfig['entityUuid'];
        }

        /** @var string|null $baseConfigId */
        $baseConfigId = $context->disableCache(function (Context $context) use ($documentTypeId) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('global', true));
            $criteria->addFilter(new EqualsFilter('documentTypeId', $documentTypeId));
            $criteria->setLimit(1);

            return $this->documentBaseConfigRepo->searchIds($criteria, $context)->firstId();
        });

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

    public function getCmsPageUuidByNames(array $names, string $oldIdentifier, string $connectionId, MigrationContextInterface $migrationContext, Context $context): string
    {
        $cmsPageMapping = $this->getMapping($connectionId, DefaultEntities::CMS_PAGE, $oldIdentifier, $context);

        if ($cmsPageMapping !== null) {
            return $cmsPageMapping['entityUuid'];
        }

        /** @var CmsPageCollection $cmsPages */
        $cmsPages = $context->disableCache(function (Context $context) use ($names) {
            $criteria = new Criteria();
            $criteria->addAssociation('translations');
            $criteria->addFilter(new EqualsAnyFilter('translations.name', $names));
            $criteria->addFilter(new EqualsFilter('locked', false));

            return $this->cmsPageRepo->search($criteria, $context)->getEntities();
        });

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

            return $cmsPage->getId();
        }

        return $oldIdentifier;
    }

    public function getCountryStateUuid(string $oldIdentifier, string $countryIso, string $countryIso3, string $countryStateCode, string $connectionId, Context $context): ?string
    {
        $countryStateMapping = $this->getMapping($connectionId, DefaultEntities::COUNTRY_STATE, $oldIdentifier, $context);

        if ($countryStateMapping !== null) {
            return $countryStateMapping['entityUuid'];
        }

        /** @var string|null $countryStateUuid */
        $countryStateUuid = $context->disableCache(function (Context $context) use ($countryStateCode, $countryIso, $countryIso3): ?string {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('shortCode', $countryStateCode));
            $criteria->addFilter(new EqualsFilter('country.iso', $countryIso));
            $criteria->addFilter(new EqualsFilter('country.iso3', $countryIso3));
            $criteria->setLimit(1);

            return $this->countryStateRepo->searchIds($criteria, $context)->firstId();
        });

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
}
