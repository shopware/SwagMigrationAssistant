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

abstract class MediaConverter extends ShopwareConverter
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
        return \array_column($converted, 'id');
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::MEDIA,
            $data['id'],
            $converted['id']
        );

        $this->updateMediaAssociation($converted);

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
