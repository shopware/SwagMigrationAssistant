<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaDefaultFolderLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaThumbnailSizeLookup;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;

#[Package('services-settings')]
class MediaFolderConverterTest extends ShopwareConverterTest
{
    protected function createConverter(
        Shopware6MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        $mediaDefaultFolderLookup = $this->createMock(MediaDefaultFolderLookup::class);
        $mediaThumbnailSizeLookup = $this->createMock(MediaThumbnailSizeLookup::class);

        static::assertIsArray($mappingArray);

        $thumbnailSizeReturnMap = [];
        foreach ($mappingArray as $mapping) {
            if ($mapping['entityName'] === DefaultEntities::MEDIA_DEFAULT_FOLDER) {
                $mediaDefaultFolderLookup->method('get')->willReturn($mapping['newIdentifier']);
            }

            if ($mapping['entityName'] === DefaultEntities::MEDIA_THUMBNAIL_SIZE) {
                $thumbnailSizeReturnMap[] = $mapping['newIdentifier'];
            }
        }

        // This is a fix for the test "02-NewThumbnailSize" where the third thumbnail size is a new one
        // use array_unshift because the 1x1 thumbnail size is the first one in the lookup
        while (\count($thumbnailSizeReturnMap) < 3) {
            array_unshift($thumbnailSizeReturnMap, null);
        }

        $mediaThumbnailSizeLookup->method('get')->willReturnOnConsecutiveCalls(...$thumbnailSizeReturnMap);

        return new MediaFolderConverter(
            $mappingService,
            $loggingService,
            $mediaDefaultFolderLookup,
            $mediaThumbnailSizeLookup,
        );
    }

    protected function createDataSet(): DataSet
    {
        return new MediaFolderDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/MediaFolder/';
    }
}
