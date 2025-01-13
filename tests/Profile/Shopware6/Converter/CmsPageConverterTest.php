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
use SwagMigrationAssistant\Migration\Mapping\Lookup\CmsPageLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CmsPageConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CmsPageDataSet;

#[Package('fundamentals@after-sales')]
class CmsPageConverterTest extends ShopwareConverterTest
{
    protected function createConverter(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        $cmsPageLookup = $this->createMock(CmsPageLookup::class);

        static::assertIsArray($mappingArray);

        foreach ($mappingArray as $mapping) {
            if ($mapping['entityName'] === DefaultEntities::CMS_PAGE) {
                $cmsPageLookup->method('getByNames')->willReturn($mapping['newIdentifier']);
            }
        }

        return new CmsPageConverter(
            $mappingService,
            $loggingService,
            $cmsPageLookup
        );
    }

    protected function createDataSet(): DataSet
    {
        return new CmsPageDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/CmsPage/';
    }
}
