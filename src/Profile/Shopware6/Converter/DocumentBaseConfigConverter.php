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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DocumentBaseConfigDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Logging\Log\UnsupportedDocumentTypeLog;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class DocumentBaseConfigConverter extends ShopwareMediaConverter
{
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

        $converted['documentTypeId'] = $this->mappingService->getDocumentTypeUuid($converted['documentType']['technicalName'], $this->context, $this->migrationContext);
        if ($converted['documentTypeId'] === null) {
            $this->loggingService->addLogEntry(new UnsupportedDocumentTypeLog($this->runId, DefaultEntities::ORDER_DOCUMENT_BASE_CONFIG, $data['id'], $data['documentType']['technicalName']));

            return new ConvertStruct(null, $data, $converted['id'] ?? null);
        }
        unset($converted['documentType']);

        if ($data['global']) {
            $converted['id'] = $this->mappingService->getGlobalDocumentBaseConfigUuid($data['id'], $converted['documentTypeId'], $this->connectionId, $this->migrationContext, $this->context);
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::ORDER_DOCUMENT_BASE_CONFIG,
            $data['id'],
            $converted['id']
        );

        foreach ($converted['salesChannels'] as &$salesChannel) {
            $salesChannel['documentTypeId'] = $this->mappingService->getDocumentTypeUuid($salesChannel['documentType']['technicalName'], $this->context, $this->migrationContext);
            unset($salesChannel['documentType']);
        }
        unset($salesChannel);

        if (isset($converted['logo'])) {
            $this->updateMediaAssociation($converted['logo']);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
