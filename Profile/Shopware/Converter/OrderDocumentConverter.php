<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeDefinition;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Document\FileGenerator\FileTypes;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Util\Random;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\DocumentTypeNotSupported;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;

abstract class OrderDocumentConverter extends ShopwareConverter
{
    /**
     * @var MediaFileServiceInterface
     */
    protected $mediaFileService;

    /**
     * @var string
     */
    protected $oldId;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var string
     */
    protected $connectionId;

    /**
     * @var Context
     */
    protected $context;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService
    ) {
        parent::__construct($mappingService, $loggingService);

        $this->mediaFileService = $mediaFileService;
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $checksum = $this->generateChecksum($data);
        $this->oldId = $data['id'];
        $this->runId = $migrationContext->getRunUuid();
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->migrationContext = $migrationContext;
        $this->context = $context;

        $oldData = $data;
        $converted = [];

        if (empty($data['hash'])) {
            $this->loggingService->addLogEntry(
                new EmptyNecessaryFieldRunLog(
                    $this->migrationContext->getRunUuid(),
                    DefaultEntities::ORDER_DOCUMENT,
                    $this->oldId,
                    'hash'
                )
            );

            return new ConvertStruct(null, $oldData);
        }

        $orderMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::ORDER,
            $data['orderID'],
            $context
        );
        if ($orderMapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->migrationContext->getRunUuid(),
                    DefaultEntities::ORDER,
                    $this->oldId,
                    DefaultEntities::ORDER_DOCUMENT
                )
            );

            return new ConvertStruct(null, $oldData);
        }
        unset($data['orderID']);
        $orderUuid = $orderMapping['entityUuid'];
        $this->mappingIds[] = $orderMapping['id'];

        $this->mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::ORDER_DOCUMENT,
            $this->oldId,
            $context,
            $checksum
        );
        $converted['id'] = $this->mapping['entityUuid'];
        $converted['orderId'] = $orderUuid;
        $converted['fileType'] = FileTypes::PDF;
        $converted['static'] = true;
        $converted['deepLinkCode'] = Random::getAlphanumericString(32);
        $converted['config'] = [];
        if (isset($data['docID'])) {
            $converted['config']['documentNumber'] = $data['docID'];
            unset($data['docID']);
        }

        $documentType = $this->getDocumentType($data['documenttype']);
        if ($documentType === null) {
            return new ConvertStruct(null, $oldData);
        }
        $converted['documentType'] = $documentType;
        unset($data['documenttype']);

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::ORDER_DOCUMENT, $migrationContext->getConnection()->getName(), ['id', 'documentID']);
        }
        unset($data['attributes']);

        $converted['documentMediaFile'] = $this->getMediaFile($data);
        unset(
            $data['id'],
            $data['hash'],
            $data['_locale'],

            // Unused but not necessary
            $data['description'],
            $data['date'],
            $data['type'],
            $data['userID'],
            $data['amount']
        );

        if (empty($data)) {
            $data = null;
        }

        $this->mapping['additionalData']['relatedMappings'] = $this->mappingIds;
        $this->mappingIds = [];
        $this->mappingService->updateMapping(
            $this->connectionId,
            DefaultEntities::ORDER_DOCUMENT,
            $this->mapping['oldIdentifier'],
            $this->mapping,
            $context
        );

        return new ConvertStruct($converted, $data, $this->mapping['id']);
    }

    protected function getDocumentType(array $data): ?array
    {
        $knownTypes = ['invoice', 'delivery_note', 'storno', 'credit_note'];

        if (!in_array($data['key'], $knownTypes, true)) {
            $this->loggingService->addLogEntry(new DocumentTypeNotSupported(
                $this->runId,
                $data['id'],
                $data['key']
            ));

            return null;
        }

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DocumentTypeDefinition::ENTITY_NAME,
            $data['key'],
            $this->context
        );
        $this->mappingIds[] = $mapping['id'];
        $documentType['id'] = $mapping['entityUuid'];
        $documentType['name'] = $data['name'];
        $documentType['technicalName'] = $data['key'];

        return $documentType;
    }

    protected function getMediaFile(array $data): array
    {
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::ORDER_DOCUMENT_MEDIA,
            $data['id'],
            $this->context
        );
        $newMedia['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $this->runId,
                'entity' => OrderDocumentDataSet::getEntity(),
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
