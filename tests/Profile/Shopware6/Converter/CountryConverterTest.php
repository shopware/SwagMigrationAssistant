<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Converter;

use _PHPStan_58d202fdd\Composer\Pcre\Preg;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryLookup;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CountryConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CountryDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;

#[Package('services-settings')]
class CountryConverterTest extends ShopwareConverterTest
{
    protected function createConverter(
        Shopware6MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = []
    ): ConverterInterface {
        $countryLookup = $this->createMock(CountryLookup::class);

        foreach ($mappingArray as $mapping) {
            if($mapping['entityName'] === DefaultEntities::COUNTRY) {
                $countryLookup->method('get')->willReturn($mapping['newIdentifier']);
            }
        }

        return new CountryConverter(
            $mappingService,
            $loggingService,
            $countryLookup
        );
    }

    protected function createDataSet(): DataSet
    {
        return new CountryDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/Country/';
    }
}
