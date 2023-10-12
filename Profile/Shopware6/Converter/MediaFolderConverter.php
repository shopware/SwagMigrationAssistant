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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Logging\Log\UnsupportedMediaDefaultFolderLog;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class MediaFolderConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === MediaFolderDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::MEDIA_FOLDER,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['defaultFolder'])) {
            $converted['defaultFolderId'] = $this->mappingService->getDefaultFolderIdByEntity($data['defaultFolder']['entity'], $this->migrationContext, $this->context);

            if ($converted['defaultFolderId'] === null) {
                $this->loggingService->addLogEntry(
                    new UnsupportedMediaDefaultFolderLog(
                        $this->migrationContext->getRunUuid(),
                        DefaultEntities::MEDIA_FOLDER,
                        $data['id'],
                        $data['defaultFolder']['entity']
                    )
                );
                unset($converted['defaultFolderId']);
            }

            unset($converted['defaultFolder']);
        }

        if (isset($converted['configuration']['mediaThumbnailSizes'])) {
            foreach ($converted['configuration']['mediaThumbnailSizes'] as $key => $size) {
                $uuid = $this->mappingService->getThumbnailSizeUuid(
                    $size['width'],
                    $size['height'],
                    $this->migrationContext,
                    $this->context
                );

                if ($uuid !== null) {
                    $converted['configuration']['mediaThumbnailSizes'][$key]['id'] = $uuid;

                    continue;
                }

                $this->getOrCreateMappingIdFacade(
                    DefaultEntities::MEDIA_THUMBNAIL_SIZE,
                    $size['width'] . '-' . $size['height'],
                    $size['id']
                );
            }
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
