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
use SwagMigrationAssistant\Migration\Mapping\Lookup\DeliveryTimeLookup;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Converter\DeliveryTimeConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DeliveryTimeDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;

#[Package('services-settings')]
class DeliveryTimeConverterTest extends ShopwareConverterTest
{
    protected function createConverter(
        Shopware6MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        ?array $mappingArray = [],
    ): ConverterInterface {
        $deliveryTimeLookup = $this->createMock(DeliveryTimeLookup::class);

        static::assertIsArray($mappingArray);

        $returnMap = [];
        foreach ($mappingArray as $mapping) {
            if ($mapping['entityName'] === DefaultEntities::DELIVERY_TIME) {
                $returnMap[] = $mapping['newIdentifier'];
                $deliveryTimeLookup->method('get')->willReturn($mapping['newIdentifier']);
            }
        }

        while (\count($returnMap) < 1) {
            $returnMap[] = 'c2b7cb2bc66a47b9a4e4cf60c9f071fb';
        }

        $deliveryTimeLookup->method('get')->willReturnOnConsecutiveCalls(...$returnMap);

        return new DeliveryTimeConverter(
            $mappingService,
            $loggingService,
            $deliveryTimeLookup
        );
    }

    protected function createDataSet(): DataSet
    {
        return new DeliveryTimeDataSet();
    }

    protected static function getFixtureBasePath(): string
    {
        return __DIR__ . '/../../../_fixtures/Shopware6/DeliveryTime/';
    }
}
