<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeDefinition;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Document\FileGenerator\FileTypes;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Random;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertDocumentTypeUnsupportedLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertSourceDataIncompleteLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DocumentTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\GlobalDocumentBaseConfigLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaDefaultFolderLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;

#[Package('fundamentals@after-sales')]
abstract class OrderDocumentConverter extends ShopwareConverter
{
    protected string $oldId;

    protected string $runId;

    protected string $connectionId;

    protected string $connectionName;

    protected Context $context;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected MediaFileServiceInterface $mediaFileService,
        protected readonly MediaDefaultFolderLookup $mediaFolderLookup,
        protected readonly DocumentTypeLookup $documentTypeLookup,
        protected readonly GlobalDocumentBaseConfigLookup $globalDocumentBaseConfigLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaUuids = [];
        foreach ($converted as $data) {
            if (!isset($data['documentMediaFile']['id'])) {
                continue;
            }

            $mediaUuids[] = $data['documentMediaFile']['id'];
        }

        return $mediaUuids;
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->oldId = $data['id'];
        $this->runId = $migrationContext->getRunUuid();
        $this->migrationContext = $migrationContext;
        $this->context = $context;

        $connection = $migrationContext->getConnection();
        $this->connectionId = $connection->getId();
        $this->connectionName = $connection->getName();

        $oldData = $data;
        $converted = [];

        if (!isset($data['documenttype']) || !\is_array($data['documenttype'])) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(DocumentDefinition::ENTITY_NAME)
                    ->withFieldName('documentType')
                    ->withFieldSourcePath('documenttype')
                    ->withSourceData($data)
                    ->withConvertedData($converted)
                    ->build(ConvertSourceDataIncompleteLog::class)
            );

            return new ConvertStruct(null, $oldData);
        }

        $orderMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::ORDER,
            $data['orderID'],
            $context
        );

        $orderId = null;
        if ($orderMapping !== null) {
            $this->mappingIds[] = $orderMapping['id'];
            $orderId = $orderMapping['entityId'];
        }
        $converted['orderId'] = $orderId;
        unset($data['orderID']);

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::ORDER_DOCUMENT,
            $this->oldId,
            $context,
            $this->checksum
        );

        $converted['id'] = $this->mainMapping['entityId'];
        $converted['fileType'] = FileTypes::PDF;
        $converted['static'] = true;
        $converted['deepLinkCode'] = Random::getAlphanumericString(32);
        if (\array_key_exists('sent', $data)) {
            $converted['sent'] = $data['sent'];
        } else {
            // In Shopware 5 "sent" not exists, so we force it to true, because we assume that if there is a document, the customer received it.
            $converted['sent'] = true;
        }

        $converted['config'] = [];
        if (isset($data['docID'])) {
            if (isset($data['documenttype'])) {
                $converted['documentType'] = $this->getDocumentType($data['documenttype']);
                $converted['config'] = $this->getBaseDocumentTypeConfig($converted['documentType']['id'], $context);
            }

            $converted['config']['documentNumber'] = $data['docID'];

            if (isset($data['documenttype']['key']) && $data['documenttype']['key'] === 'invoice') {
                $converted['config']['custom']['invoiceNumber'] = $data['docID'];
            }

            unset($data['docID'], $data['documenttype']);
        }

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::ORDER_DOCUMENT, $this->connectionName, ['id', 'documentID'], $this->context);
        }
        unset($data['attributes']);

        $converted['documentMediaFile'] = $this->getMediaFile($data);

        if ($converted['documentMediaFile'] === null) {
            return new ConvertStruct(null, $oldData);
        }

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
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $data, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function getDocumentType(array $data): array
    {
        $documentType = [];
        $mappedKey = $this->mapDocumentType($data['key']);
        $documentTypeUuid = $this->documentTypeLookup->get($mappedKey, $this->context);
        if ($documentTypeUuid !== null) {
            $documentType['id'] = $documentTypeUuid;

            return $documentType;
        }

        $this->loggingService->log(
            MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                ->withEntityName(DocumentDefinition::ENTITY_NAME)
                ->withFieldName('documentType')
                ->withFieldSourcePath('key')
                ->withSourceData($data)
                ->withConvertedData($documentType)
                ->build(ConvertDocumentTypeUnsupportedLog::class)
        );

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DocumentTypeDefinition::ENTITY_NAME,
            $mappedKey,
            $this->context
        );
        $this->mappingIds[] = $mapping['id'];

        $documentType['id'] = $mapping['entityId'];
        $documentType['name'] = $data['name'];
        $documentType['technicalName'] = $mappedKey;

        return $documentType;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getBaseDocumentTypeConfig(string $documentTypeId, Context $context): array
    {
        $documentConfigId = $this->globalDocumentBaseConfigLookup->get($documentTypeId, $context);
        if ($documentConfigId === null) {
            return [];
        }

        $documentConfig = $this->globalDocumentBaseConfigLookup->getBaseConfig($documentConfigId, $context);
        if ($documentConfig === null) {
            return [];
        }

        return $documentConfig;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    protected function getMediaFile(array $data): ?array
    {
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::ORDER_DOCUMENT_MEDIA,
            $data['id'],
            $this->context
        );

        $newMedia = [];
        $newMedia['id'] = $mapping['entityId'];
        $this->mappingIds[] = $mapping['id'];

        if (!empty($data['hash'])) {
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
        } else {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                    ->withEntityName(OrderDocumentDataSet::getEntity())
                    ->withFieldName('hash')
                    ->withFieldSourcePath('hash')
                    ->withSourceData($data)
                    ->withConvertedData($newMedia)
                    ->build(ConvertSourceDataIncompleteLog::class)
            );

            return null;
        }

        $newMedia['private'] = true;
        $this->convertValue($newMedia, 'title', $data, 'hash');

        $albumUuid = $this->mediaFolderLookup->get(DocumentDefinition::ENTITY_NAME, $this->context);
        if ($albumUuid !== null) {
            $newMedia['mediaFolderId'] = $albumUuid;
        }

        return $newMedia;
    }

    protected function mapDocumentType(string $sourceDocumentType): string
    {
        return match ($sourceDocumentType) {
            'cancellation' => 'storno',
            'credit' => 'credit_note',
            default => $sourceDocumentType,
        };
    }
}
