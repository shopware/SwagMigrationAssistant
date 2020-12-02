<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;

abstract class CategoryConverter extends ShopwareConverter
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

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaIds = [];
        foreach ($converted as $category) {
            if (isset($category['media']['id'])) {
                $mediaIds[] = $category['media']['id'];
            }
        }

        return $mediaIds;
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::CATEGORY,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::CATEGORY
        );

        if (isset($converted['media'])) {
            $this->updateMediaAssociation($converted['media']);
        }

        unset(
            // ToDo implement if these associations are migrated
            $converted['cmsPageId']
        );

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
