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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;
use SwagMigrationAssistant\Profile\Shopware63\Converter\Shopware63ProductConverter;

class ProductConverterTest extends ShopwareConverterTest
{
    protected function createConverter(Shopware6MappingServiceInterface $mappingService, LoggingServiceInterface $loggingService, MediaFileServiceInterface $mediaFileService): ConverterInterface
    {
        return new Shopware63ProductConverter($mappingService, $loggingService, $mediaFileService);
    }

    protected function createDataSet(): DataSet
    {
        return new ProductDataSet();
    }

    protected function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/Product/';
    }
}
