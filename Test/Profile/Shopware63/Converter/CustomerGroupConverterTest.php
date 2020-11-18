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
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware63\Converter\Shopware63CustomerGroupConverter;

class CustomerGroupConverterTest extends ShopwareConverterTest
{
    protected function createConverter(MappingServiceInterface $mappingService, LoggingServiceInterface $loggingService): ConverterInterface
    {
        return new Shopware63CustomerGroupConverter($mappingService, $loggingService);
    }

    protected function createDataSet(): DataSet
    {
        return new CustomerGroupDataSet();
    }

    protected function getConverterTestClassName(): string
    {
        return self::class;
    }

    protected function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/CustomerGroup/';
    }
}
