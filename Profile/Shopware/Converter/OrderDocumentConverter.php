<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeDefinition;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Document\FileGenerator\FileTypes;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Util\Random;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware\Logging\LogTypes;

abstract class OrderDocumentConverter extends ShopwareConverter
{
    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    /**
     * @var LoggingServiceInterface
     */
    protected $loggingService;

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

    /**
     * @var MigrationContextInterface
     */
    protected $migrationContext;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
        $this->mediaFileService = $mediaFileService;
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
                LogTypes::ASSOCIATION_REQUIRED_MISSING,
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
            DefaultEntities::ORDER_DOCUMENT,
            $this->oldId,
            $context
        );

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

        $converted['documentMediaFile'] = $this->getMediaFile($data);
        unset(
            $data['id'],
            $data['description'],
            $data['hash']
        );

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    protected function getDocumentType(array $data): ?array
    {
        $knownTypes = ['invoice', 'delivery_note', 'storno', 'credit_note'];

        if (!in_array($data['key'], $knownTypes, true)) {
            return null;
        }

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

    protected function getMediaFile(array $data): array
    {
        $newMedia['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::ORDER_DOCUMENT_MEDIA,
            $data['id'],
            $this->context
        );

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
