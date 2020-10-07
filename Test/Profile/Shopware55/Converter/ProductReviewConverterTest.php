<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductReviewDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductReviewConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class ProductReviewConverterTest extends TestCase
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var Shopware55ProductReviewConverter
     */
    private $converter;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var array
     */
    private $products;

    /**
     * @var array
     */
    private $salesChannel;

    /**
     * @var array
     */
    private $customer;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->converter = new Shopware55ProductReviewConverter($this->mappingService, $this->loggingService);

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

        $this->products['198'] = $this->mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::PRODUCT_MAIN,
            '198',
            $this->context
        );

        $this->products['145'] = $this->mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::PRODUCT_MAIN,
            '145',
            $this->context
        );

        $this->salesChannel['1'] = $this->mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::SALES_CHANNEL,
            '1',
            $this->context
        );

        $this->customer['max@mustermann.de'] = $this->mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::CUSTOMER,
            'max@mustermann.de',
            $this->context
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
