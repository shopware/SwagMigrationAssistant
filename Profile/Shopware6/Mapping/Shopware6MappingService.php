<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Mapping;

use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\NumberRange\Aggregate\NumberRangeType\NumberRangeTypeEntity;
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
    protected $salutationRepo;

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
        EntityRepositoryInterface $salutationRepo
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
        $this->salutationRepo = $salutationRepo;
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

        $result = $context->disableCache(function (Context $context) use ($type): EntitySearchResult {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('technicalName', $type));

            return $this->mailTemplateTypeRepo->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var MailTemplateTypeEntity|null $mailTemplateType */
            $mailTemplateType = $result->getEntities()->first();

            if ($mailTemplateType === null) {
                return null;
            }

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::NUMBER_RANGE_TYPE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $mailTemplateType->getId(),
                ]
            );

            return $mailTemplateType->getId();
        }

        return null;
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

        $result = $context->disableCache(function (Context $context) use ($type): EntitySearchResult {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('technicalName', $type));

            return $this->numberRangeTypeRepo->search($criteria, $context);
        });

        if ($result->getTotal() > 0) {
            /** @var NumberRangeTypeEntity|null $numberRangeType */
            $numberRangeType = $result->getEntities()->first();

            if ($numberRangeType === null) {
                return null;
            }

            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::NUMBER_RANGE_TYPE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $numberRangeType->getId(),
                ]
            );

            return $numberRangeType->getId();
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

        /** @var IdSearchResult $result */
        $result = $context->disableCache(function (Context $context) use ($salutationKey) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('salutationKey', $salutationKey));
            $criteria->setLimit(1);

            return $this->salutationRepo->searchIds($criteria, $context);
        });

        $salutationId = $result->firstId();

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
}
