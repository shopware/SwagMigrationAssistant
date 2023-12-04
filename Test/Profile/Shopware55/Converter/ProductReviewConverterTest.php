<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductReviewDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductReviewConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

#[Package('services-settings')]
class ProductReviewConverterTest extends TestCase
{
    private Shopware55ProductReviewConverter $converter;

    private MigrationContext $migrationContext;

    /**
     * @var array<array<string, string>>
     */
    private array $products;

    /**
     * @var array<array<string, string>>
     */
    private array $salesChannel;

    /**
     * @var array<array<string, string>>
     */
    private array $customer;

    protected function setUp(): void
    {
        $context = Context::createDefaultContext();
        $mappingService = new DummyMappingService();
        $loggingService = new DummyLoggingService();
        $this->converter = new Shopware55ProductReviewConverter($mappingService, $loggingService);

        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId($connectionId);
        $connection->setName('ConnectionName');
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new ProductReviewDataSet(),
            0,
            250
        );

        $this->products['198'] = $mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::PRODUCT_MAIN,
            '198',
            $context
        );

        $this->products['145'] = $mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::PRODUCT_MAIN,
            '145',
            $context
        );

        $this->salesChannel['1'] = $mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::SALES_CHANNEL,
            '1',
            $context
        );

        $this->customer['max@mustermann.de'] = $mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::CUSTOMER,
            'max@mustermann.de',
            $context
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->converter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $productReviewData = require __DIR__ . '/../../../_fixtures/product_review_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($productReviewData[1], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertSame($this->products['145']['entityUuid'], $converted['productId']);
        static::assertSame($this->customer['max@mustermann.de']['entityUuid'], $converted['customerId']);
        static::assertSame('max@mustermann.de', $converted['externalEmail']);
        static::assertSame($this->salesChannel['1']['entityUuid'], $converted['salesChannelId']);
    }

    public function testConvertWithoutCustomer(): void
    {
        $productReviewData = require __DIR__ . '/../../../_fixtures/product_review_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($productReviewData[0], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertSame($this->products['198']['entityUuid'], $converted['productId']);
        static::assertArrayNotHasKey('customerId', $converted);
        static::assertArrayNotHasKey('externalEmail', $converted);
        static::assertSame($this->salesChannel['1']['entityUuid'], $converted['salesChannelId']);
    }
}
