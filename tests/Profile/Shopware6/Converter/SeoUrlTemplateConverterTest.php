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
use SwagMigrationAssistant\Migration\Mapping\Lookup\SeoUrlTemplateLookup;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SeoUrlTemplateConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SeoUrlTemplateDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;

#[Package('services-settings')]
class SeoUrlTemplateConverterTest extends ShopwareConverterTest
{
    protected function createConverter(
        Shopware6MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        $seoUrlTemplateLookup = $this->createMock(SeoUrlTemplateLookup::class);

        $returnMap = [];
        static::assertIsArray($mappingArray);
        foreach ($mappingArray as $mapping) {
            if ($mapping['entityName'] === DefaultEntities::SEO_URL_TEMPLATE) {
                $returnMap[] = $mapping['newIdentifier'];
                $seoUrlTemplateLookup->method('get')->willReturn($mapping['newIdentifier']);
            }
        }

        $seoUrlTemplateLookup->method('get')->willReturnOnConsecutiveCalls(...$returnMap);

        return new SeoUrlTemplateConverter($mappingService, $loggingService, $seoUrlTemplateLookup);
    }

    protected function createDataSet(): DataSet
    {
        return new SeoUrlTemplateDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/SeoUrlTemplate/';
    }
}
