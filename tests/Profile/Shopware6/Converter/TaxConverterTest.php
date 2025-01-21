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
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\TaxConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\TaxDataSet;

#[Package('fundamentals@after-sales')]
class TaxConverterTest extends ShopwareConverterTest
{
    protected function createConverter(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        $taxLookup = $this->createMock(TaxLookup::class);

        static::assertIsArray($mappingArray);

        foreach ($mappingArray as $mapping) {
            if ($mapping['entityName'] === DefaultEntities::TAX) {
                $taxLookup->method('getByTaxRateAndName')->willReturn($mapping['newIdentifier']);
            }
        }

        return new TaxConverter(
            $mappingService,
            $loggingService,
            $taxLookup
        );
    }

    protected function createDataSet(): DataSet
    {
        return new TaxDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/Tax/';
    }
}
