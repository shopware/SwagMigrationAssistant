<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;

#[Package('services-settings')]
abstract class ShopwareMediaConverter extends ShopwareConverter
{
    public function __construct(
        Shopware6MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected MediaFileServiceInterface $mediaFileService
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    protected function updateMediaAssociation(array &$mediaArray, ?string $entity = null): void
    {
        if (isset($mediaArray['translations'])) {
            $this->updateAssociationIds(
                $mediaArray['translations'],
                DefaultEntities::LANGUAGE,
                'languageId',
                $entity ?? DefaultEntities::MEDIA
            );
        }

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $this->runId,
                'entity' => $entity ?? DefaultEntities::MEDIA,
                'uri' => $mediaArray['url'],
                'fileName' => $mediaArray['fileName'],
                'fileSize' => (int) $mediaArray['fileSize'],
                'mediaId' => $mediaArray['id'],
            ]
        );

        $mediaArray['hasFile'] = false;
        unset(
            $mediaArray['url'],
            $mediaArray['fileSize'],
            $mediaArray['fileExtension']
        );
    }
}
