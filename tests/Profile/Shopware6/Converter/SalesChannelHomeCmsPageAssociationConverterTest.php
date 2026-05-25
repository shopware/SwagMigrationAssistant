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
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SalesChannelHomeCmsPageAssociationConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalesChannelHomeCmsPageAssociationDataSet;

#[Package('fundamentals@after-sales')]
class SalesChannelHomeCmsPageAssociationConverterTest extends ShopwareConverterTest
{
    protected function createConverter(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        return new SalesChannelHomeCmsPageAssociationConverter($mappingService, $loggingService);
    }

    protected function createDataSet(): DataSet
    {
        return new SalesChannelHomeCmsPageAssociationDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/SalesChannelHomeCmsPageAssociation/';
    }
}
