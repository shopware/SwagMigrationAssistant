<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DocumentTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\GlobalDocumentBaseConfigLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DocumentBaseConfigDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Logging\Log\UnsupportedDocumentTypeLog;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class DocumentBaseConfigConverter extends ShopwareMediaConverter
{
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected MediaFileServiceInterface $mediaFileService,
        protected readonly DocumentTypeLookup $documentTypeLookup,
        protected readonly GlobalDocumentBaseConfigLookup $globalDocumentBaseConfigLookup,
    ) {
        parent::__construct($mappingService, $loggingService, $mediaFileService);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === DocumentBaseConfigDataSet::getEntity();
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaIds = [];
        foreach ($converted as $document) {
            if (isset($document['logo']['id'])) {
                $mediaIds[] = $document['logo']['id'];
            }
        }

        return $mediaIds;
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $converted['documentTypeId'] = $this->documentTypeLookup->get($converted['documentType']['technicalName'], $this->context);
        if ($converted['documentTypeId'] === null) {
            $this->loggingService->addLogEntry(new UnsupportedDocumentTypeLog($this->runId, DefaultEntities::ORDER_DOCUMENT_BASE_CONFIG, $data['id'], $data['documentType']['technicalName']));

            return new ConvertStruct(null, $data, $converted['id'] ?? null);
        }
        unset($converted['documentType']);

        if ($data['global']) {
            $converted['id'] = $this->globalDocumentBaseConfigLookup->get($converted['documentTypeId'], $this->context);
            if ($converted['id'] === null) {
                $converted['id'] = $data['id'];
            }
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::ORDER_DOCUMENT_BASE_CONFIG,
            $data['id'],
            $converted['id']
        );

        foreach ($converted['salesChannels'] as &$salesChannel) {
            $salesChannel['documentTypeId'] = $this->documentTypeLookup->get($salesChannel['documentType']['technicalName'], $this->context);
            unset($salesChannel['documentType']);
        }
        unset($salesChannel);

        if (isset($converted['logo'])) {
            $this->updateMediaAssociation($converted['logo']);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
