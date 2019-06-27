<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Converter;

use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeDefinition;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Document\FileGenerator\FileTypes;
use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class OrderDocumentConverter extends Shopware55Converter
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var MediaFileServiceInterface
     */
    private $mediaFileService;

    /**
     * @var string
     */
    private $oldId;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
        $this->mediaFileService = $mediaFileService;
    }

    public function getSupportedEntityName(): string
    {
        return DefaultEntities::ORDER_DOCUMENTS;
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->oldId = $data['id'];
        $this->runId = $migrationContext->getRunUuid();
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->migrationContext = $migrationContext;
        $this->context = $context;

        $oldData = $data;
        $converted = [];

        $orderUuid = $this->mappingService->getUuid($this->connectionId, DefaultEntities::ORDER, $data['orderID'], $context);
        if ($orderUuid === null) {
            $this->loggingService->addWarning(
                $migrationContext->getRunUuid(),
                Shopware55LogTypes::ASSOCIATION_REQUIRED_MISSING,
                'Associated order not found',
                'Order for the order document can not be found.',
                [
                    'data' => $data,
                    'missingEntity' => 'order',
                    'requiredFor' => 'order_document',
                    'missingImportEntity' => 'order_document',
                ]
            );

            return new ConvertStruct(null, $oldData);
        }

        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::ORDER_DOCUMENTS,
            $this->oldId,
            $context
        );

        $converted['orderId'] = $orderUuid;
        $converted['fileType'] = FileTypes::PDF;
        $converted['static'] = true;

        $converted['documentType'] = $this->getDocumentType($data['documenttype']);
        unset($data['documenttype']);

        $converted['documentMediaFile'] = $this->getMediaFile($data);
        $this->convertValue($converted, 'deepLinkCode', $data, 'docID');
        unset(
            $data['id'],
            $data['description'],
            $data['hash'],
        );

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    private function getDocumentType(array $data): array
    {
        $documentType['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DocumentTypeDefinition::ENTITY_NAME,
            $data['key'],
            $this->context
        );

        $documentType['name'] = $data['name'];
        $documentType['technicalName'] = $data['key'];

        return $documentType;
    }

    private function getMediaFile(array $data): array
    {
        $newMedia['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $data['id'],
            $this->context
        );

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $this->runId,
                'uri' => $data['hash'],
                'fileName' => $data['hash'],
                'fileSize' => 0,
                'mediaId' => $newMedia['id'],
            ]
        );

        $newMedia['private'] = true;
        $this->convertValue($newMedia, 'title', $data, 'hash');

        $albumUuid = $this->mappingService->getDefaultFolderIdByEntity(
            DocumentDefinition::ENTITY_NAME,
            $this->migrationContext,
            $this->context
        );

        if ($albumUuid !== null) {
            $newMedia['mediaFolderId'] = $albumUuid;
        }

        return $newMedia;
    }
}
