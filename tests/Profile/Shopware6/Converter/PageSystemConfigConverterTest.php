<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemConfigLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\PageSystemConfigConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\PageSystemConfigDataSet;

#[Package('fundamentals@after-sales')]
class PageSystemConfigConverterTest extends ShopwareConverterTest
{
    use KernelTestBehaviour;

    protected function createConverter(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        $systemConfigLookup = $this->createMock(SystemConfigLookup::class);

        static::assertIsArray($mappingArray);

        foreach ($mappingArray as $mapping) {
            if ($mapping['entityName'] === DefaultEntities::SYSTEM_CONFIG) {
                $systemConfigLookup->method('get')->willReturn($mapping['newIdentifier']);
            }
        }

        return new PageSystemConfigConverter(
            $mappingService,
            $loggingService,
            $systemConfigLookup
        );
    }

    protected function createDataSet(): DataSet
    {
        return new PageSystemConfigDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/PageSystemConfig/';
    }
}
