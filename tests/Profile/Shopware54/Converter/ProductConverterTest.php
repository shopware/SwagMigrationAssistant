<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware54\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ProductConverter;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class ProductConverterTest extends TestCase
{
    public function testConvertShouldConvertSeoMainCategories(): void
    {
        $loggerMock = $this->createMock(LoggingServiceInterface::class);
        $mediaFileServiceMock = $this->createMock(MediaFileServiceInterface::class);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware54Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setName('shopware');

        $context = Context::createDefaultContext();

        $mappingServiceMock = new DummyMappingService();
        $mappingServiceMock->getOrCreateMapping($connection->getId(), DefaultEntities::SALES_CHANNEL, '1', $context, null, [], Uuid::randomHex());
        $mappingServiceMock->getOrCreateMapping($connection->getId(), DefaultEntities::SALES_CHANNEL, '3', $context, null, [], Uuid::randomHex());
        $mappingServiceMock->getOrCreateMapping($connection->getId(), DefaultEntities::CATEGORY, '15', $context, null, [], Uuid::randomHex());
        $mappingServiceMock->getOrCreateMapping($connection->getId(), DefaultEntities::CATEGORY, '51', $context, null, [], Uuid::randomHex());
        $mappingServiceMock->getOrCreateMapping($connection->getId(), DefaultEntities::CURRENCY, 'EUR', $context, null, [], Uuid::randomHex());

        $converter = new Shopware54ProductConverter($mappingServiceMock, $loggerMock, $mediaFileServiceMock);

        $data = require __DIR__ . '/_fixtures/product_with_seo_main_category.php';

        $runId = Uuid::randomHex();

        $migrationContext = new MigrationContext(
            new Shopware54Profile(),
            $connection,
            $runId,
            new ProductDataSet(),
            0,
            250
        );

        $convertedDataResult = $converter->convert($data[0], $context, $migrationContext);
        $convertedResult = $convertedDataResult->getConverted();
        static::assertIsArray($convertedResult);
        static::assertArrayHasKey('mainCategories', $convertedResult);

        $result = $convertedResult['mainCategories'];

        foreach ($result as $mainSeoCategory) {
            static::assertArrayHasKey('categoryId', $mainSeoCategory);
            static::assertArrayHasKey('salesChannelId', $mainSeoCategory);

            static::assertTrue(Uuid::isValid($mainSeoCategory['categoryId']));
            static::assertTrue(Uuid::isValid($mainSeoCategory['salesChannelId']));
        }
    }
}
