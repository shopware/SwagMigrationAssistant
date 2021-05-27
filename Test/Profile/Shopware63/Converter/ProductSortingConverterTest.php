<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware63\Converter;

use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductSortingDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;
use SwagMigrationAssistant\Profile\Shopware63\Converter\Shopware63ProductSortingConverter;

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

    protected function createConverter(Shopware6MappingServiceInterface $mappingService, LoggingServiceInterface $loggingService, MediaFileServiceInterface $mediaFileService): ConverterInterface
    {
        return new Shopware63ProductSortingConverter($mappingService, $loggingService);
    }

    protected function createDataSet(): DataSet
    {
        return new ProductSortingDataSet();
    }

    protected function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/ProductSorting/';
    }
}
