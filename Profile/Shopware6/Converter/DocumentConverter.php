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
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Logging\Log\UnsupportedDocumentTypeLog;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class DocumentConverter extends ShopwareMediaConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === DocumentDataSet::getEntity();
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaIds = [];
        foreach ($converted as $document) {
            if (isset($document['documentMediaFile']['id'])) {
                $mediaIds[] = $document['documentMediaFile']['id'];
            } else {
                $mediaIds[] = $document['id'];
            }
        }

        return $mediaIds;
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::ORDER_DOCUMENT,
            $data['id'],
            $converted['id']
        );

        $converted['documentTypeId'] = $this->mappingService->getDocumentTypeUuid($converted['documentType']['technicalName'], $this->context, $this->migrationContext);
        if ($converted['documentTypeId'] === null) {
            $this->loggingService->addLogEntry(new UnsupportedDocumentTypeLog($this->runId, DefaultEntities::ORDER_DOCUMENT, $data['id'], $data['documentType']['technicalName']));

            return new ConvertStruct(null, $data, $this->mainMapping['id'] ?? null);
        }
        unset($converted['documentType']);

        if (isset($converted['config']['documentTypeId'])) {
            $converted['config']['documentTypeId'] = $converted['documentTypeId'];
        }

        if (isset($converted['documentMediaFile'])) {
            $this->updateMediaAssociation($converted['documentMediaFile'], DocumentDataSet::getEntity());
        }

        if (isset($converted['generateUrl'])) {
            $this->mediaFileService->saveMediaFile(
                [
                    'runId' => $this->runId,
                    'entity' => DefaultEntities::ORDER_DOCUMENT_GENERATED,
                    'uri' => $converted['generateUrl'],
                    'fileName' => $converted['id'],
                    'fileSize' => 0,
                    // The mediaId is here the documentId
                    'mediaId' => $converted['id'],
                ]
            );

            unset($converted['generateUrl']);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
