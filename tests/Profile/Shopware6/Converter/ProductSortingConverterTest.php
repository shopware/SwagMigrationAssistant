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
use SwagMigrationAssistant\Migration\Mapping\Lookup\ProductSortingLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ProductSortingConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductSortingDataSet;

#[Package('services-settings')]
class ProductSortingConverterTest extends ShopwareConverterTest
{
    protected function loadMapping(array $mappingArray): void
    {
        parent::loadMapping($mappingArray);

        foreach ($mappingArray as $mapping) {
            $this->mappingService->createMapping(
                'dummy-connection-id',
                $mapping['entityName'],
                $mapping['oldIdentifier'],
                null,
                null,
                $mapping['newIdentifier']
            );
        }
    }

    protected function createConverter(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        $productSortingLookup = $this->createMock(ProductSortingLookup::class);

        static::assertIsArray($mappingArray);

        foreach ($mappingArray as $mapping) {
            if ($mapping['entityName'] === DefaultEntities::PRODUCT_SORTING) {
                $productSortingLookup->method('get')->willReturn($mapping['newIdentifier']);

                if ($mapping['oldIdentifier'] === 'is-locked') {
                    $productSortingLookup->method('getIsLocked')->willReturnCallback(function () {
                        return true;
                    });
                }
            }
        }

        return new ProductSortingConverter(
            $mappingService,
            $loggingService,
            $productSortingLookup,
        );
    }

    protected function createDataSet(): DataSet
    {
        return new ProductSortingDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/ProductSorting/';
    }
}
