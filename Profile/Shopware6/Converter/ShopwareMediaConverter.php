<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;

abstract class ShopwareMediaConverter extends ShopwareConverter
{
    /**
     * @var MediaFileServiceInterface
     */
    protected $mediaFileService;

    public function __construct(
        Shopware6MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService
    ) {
        parent::__construct($mappingService, $loggingService);
        $this->mediaFileService = $mediaFileService;
    }

    protected function updateMediaAssociation(array &$mediaArray, ?string $entity = null): void
    {
        if (isset($mediaArray['translations'])) {
            $this->updateAssociationIds(
                $mediaArray['translations'],
                DefaultEntities::LANGUAGE,
                'languageId',
                DefaultEntities::MEDIA
            );
        }

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $this->runId,
                'entity' => $entity ?? MediaDataSet::getEntity(),
                'uri' => $mediaArray['url'],
                'fileName' => $mediaArray['fileName'],
                'fileSize' => (int) $mediaArray['fileSize'],
                'mediaId' => $mediaArray['id'],
            ]
        );

        $mediaArray['hasFile'] = false;
        unset(
            $mediaArray['url'],
            $mediaArray['fileSize']
        );
    }
}
