<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Rule\GoodsPriceRule;
use Shopware\Core\Checkout\Cart\Rule\LineItemOfManufacturerRule;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\Checkout\Customer\Rule\CustomerGroupRule;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Rule\Container\OrRule;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\PromotionDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55PromotionConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class PromotionConverterTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var Shopware55PromotionConverter
     */
    private $converter;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var string
     */
    private $manufacturerId;

    /**
     * @var array
     */
    private $restrictedProducts;

    /**
     * @var string
     */
    private $customerGroup;

    /**
     * @var string
     */
    private $salesChannel;

    /**
     * @var string
     */
    private $customer;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->converter = new Shopware55PromotionConverter($this->mappingService, $this->loggingService, $salesChannelRepo);

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connectionId = $connection->getId();

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new PromotionDataSet(),
            0,
            250
        );

        $this->manufacturerId = Uuid::randomHex();
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_MANUFACTURER,
            '7',
            $this->context,
            null,
            null,
            $this->manufacturerId
        );

        $this->restrictedProducts = [
            'SW10002.1' => Uuid::randomHex(),
            'SW10002.2' => Uuid::randomHex(),
            'SW10002.3' => Uuid::randomHex(),
        ];
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            'SW10002.1',
            $this->context,
            null,
            null,
            $this->restrictedProducts['SW10002.1']
        );

        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            'SW10002.2',
            $this->context,
            null,
            null,
            $this->restrictedProducts['SW10002.2']
        );

        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            'SW10002.3',
            $this->context,
            null,
            null,
            $this->restrictedProducts['SW10002.3']
        );

        $this->customerGroup = Uuid::randomHex();
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            '5',
            $this->context,
            null,
            null,
            $this->customerGroup
        );

        $this->salesChannel = Uuid::randomHex();
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            '8',
            $this->context,
            null,
            null,
            $this->salesChannel
        );

        $this->customer = Uuid::randomHex();
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER,
            '8',
            $this->context,
            null,
            null,
            $this->customer
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->converter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);
        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedSalesChannel(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData['0']['subshopID'] = '8';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(1, $converted['salesChannels']);
        static::assertSame($this->salesChannel, $converted['salesChannels']['0']['salesChannelId']);
        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedSalesChannelWithoutMapping(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData['0']['subshopID'] = '9';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNotNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertArrayNotHasKey('salesChannels', $converted);
        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);
        static::assertSame(DefaultEntities::SALES_CHANNEL, $logs[0]['parameters']['missingEntity']);
        static::assertSame(DefaultEntities::PROMOTION, $logs[0]['parameters']['requiredFor']);
        static::assertSame('9', $logs[0]['parameters']['sourceId']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedManufacturer(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['bindtosupplier'] = '7';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertFalse($converted['discounts'][0]['considerAdvancedRules']);
        static::assertArrayNotHasKey('discountRules', $converted['discounts'][0]);

        static::assertCount(1, $converted['cartRules']);
        static::assertCount(3, $converted['cartRules'][0]['conditions']);
        static::assertSame((new LineItemOfManufacturerRule())->getName(), $converted['cartRules'][0]['conditions'][2]['type']);
        static::assertCount(1, $converted['cartRules'][0]['conditions'][2]['value']['manufacturerIds']);
        static::assertSame($this->manufacturerId, $converted['cartRules'][0]['conditions'][2]['value']['manufacturerIds'][0]);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedManufacturerWithDiscountRule(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['bindtosupplier'] = '7';
        $promotionData[0]['strict'] = '1';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertTrue($converted['discounts'][0]['considerAdvancedRules']);
        static::assertCount(1, $converted['discounts'][0]['discountRules']);
        static::assertCount(3, $converted['discounts'][0]['discountRules'][0]['conditions']);
        static::assertSame((new LineItemOfManufacturerRule())->getName(), $converted['discounts'][0]['discountRules'][0]['conditions'][2]['type']);
        static::assertSame([$this->manufacturerId], $converted['discounts'][0]['discountRules'][0]['conditions'][2]['value']['manufacturerIds']);
        static::assertSame((new OrRule())->getName(), $converted['discounts'][0]['discountRules'][0]['conditions'][1]['type']);

        static::assertCount(1, $converted['cartRules']);
        static::assertCount(3, $converted['cartRules'][0]['conditions']);
        static::assertSame((new LineItemOfManufacturerRule())->getName(), $converted['cartRules'][0]['conditions'][2]['type']);
        static::assertCount(1, $converted['cartRules'][0]['conditions'][2]['value']['manufacturerIds']);
        static::assertSame($this->manufacturerId, $converted['cartRules'][0]['conditions'][2]['value']['manufacturerIds'][0]);
        static::assertSame((new AndRule())->getName(), $converted['cartRules'][0]['conditions'][1]['type']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedNotMappedManufacturer(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['bindtosupplier'] = '8';
        $promotionData[0]['strict'] = '1';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNotNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);
        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertFalse($converted['discounts'][0]['considerAdvancedRules']);
        static::assertArrayNotHasKey('cartRules', $converted);

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);
        static::assertSame(DefaultEntities::PRODUCT_MANUFACTURER, $logs[0]['parameters']['missingEntity']);
        static::assertSame(DefaultEntities::PROMOTION_DISCOUNT, $logs[0]['parameters']['requiredFor']);
        static::assertSame('8', $logs[0]['parameters']['sourceId']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedProducts(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['restrictarticles'] = ';SW10002.1;SW10002.2;SW10002.3;';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertFalse($converted['discounts'][0]['considerAdvancedRules']);
        static::assertArrayNotHasKey('discountRules', $converted['discounts'][0]);

        static::assertCount(1, $converted['cartRules']);
        static::assertCount(3, $converted['cartRules'][0]['conditions']);
        static::assertSame((new LineItemRule())->getName(), $converted['cartRules'][0]['conditions'][2]['type']);
        static::assertCount(3, $converted['cartRules'][0]['conditions'][2]['value']['identifiers']);
        static::assertSame(\array_values($this->restrictedProducts), $converted['cartRules'][0]['conditions'][2]['value']['identifiers']);
        static::assertSame((new AndRule())->getName(), $converted['cartRules'][0]['conditions'][1]['type']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedProductsWithoutMapping(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['restrictarticles'] = ';SW10008.1;SW10008.2;SW10008.3;';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNotNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertFalse($converted['discounts'][0]['considerAdvancedRules']);
        static::assertArrayNotHasKey('discountRules', $converted['discounts'][0]);
        static::assertArrayNotHasKey('cartRules', $converted['discounts'][0]);

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(3, $logs);
        static::assertSame(DefaultEntities::PRODUCT, $logs[0]['parameters']['missingEntity']);
        static::assertSame(DefaultEntities::PROMOTION, $logs[0]['parameters']['requiredFor']);
        static::assertSame('SW10008.1', $logs[0]['parameters']['sourceId']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedProductsWithoutOneMapping(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['restrictarticles'] = ';SW10002.1;SW10002.2;SW10008.3;';
        unset($this->restrictedProducts['SW10002.3']);

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertFalse($converted['discounts'][0]['considerAdvancedRules']);
        static::assertArrayNotHasKey('discountRules', $converted['discounts'][0]);

        static::assertCount(1, $converted['cartRules']);
        static::assertCount(3, $converted['cartRules'][0]['conditions']);
        static::assertSame((new LineItemRule())->getName(), $converted['cartRules'][0]['conditions'][2]['type']);
        static::assertCount(2, $converted['cartRules'][0]['conditions'][2]['value']['identifiers']);
        static::assertSame(\array_values($this->restrictedProducts), $converted['cartRules'][0]['conditions'][2]['value']['identifiers']);
        static::assertSame((new AndRule())->getName(), $converted['cartRules'][0]['conditions'][1]['type']);

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);
        static::assertSame(DefaultEntities::PRODUCT, $logs[0]['parameters']['missingEntity']);
        static::assertSame(DefaultEntities::PROMOTION, $logs[0]['parameters']['requiredFor']);
        static::assertSame('SW10008.3', $logs[0]['parameters']['sourceId']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedProductsWithDiscountRule(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['restrictarticles'] = ';SW10002.1;SW10002.2;SW10002.3;';
        $promotionData[0]['strict'] = 1;

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertTrue($converted['discounts'][0]['considerAdvancedRules']);
        static::assertCount(1, $converted['discounts'][0]['discountRules']);
        static::assertCount(3, $converted['discounts'][0]['discountRules'][0]['conditions']);
        static::assertSame((new LineItemRule())->getName(), $converted['discounts'][0]['discountRules'][0]['conditions'][2]['type']);
        static::assertSame(\array_values($this->restrictedProducts), $converted['discounts'][0]['discountRules'][0]['conditions'][2]['value']['identifiers']);
        static::assertSame((new OrRule())->getName(), $converted['discounts'][0]['discountRules'][0]['conditions'][1]['type']);

        static::assertCount(1, $converted['cartRules']);
        static::assertCount(3, $converted['cartRules'][0]['conditions']);
        static::assertSame((new LineItemRule())->getName(), $converted['cartRules'][0]['conditions'][2]['type']);
        static::assertCount(3, $converted['cartRules'][0]['conditions'][2]['value']['identifiers']);
        static::assertSame(\array_values($this->restrictedProducts), $converted['cartRules'][0]['conditions'][2]['value']['identifiers']);
        static::assertSame((new AndRule())->getName(), $converted['cartRules'][0]['conditions'][1]['type']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithMinimumPrice(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['minimumcharge'] = '25';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertFalse($converted['discounts'][0]['considerAdvancedRules']);
        static::assertArrayNotHasKey('discountRules', $converted['discounts'][0]);

        static::assertCount(1, $converted['cartRules']);
        static::assertCount(3, $converted['cartRules'][0]['conditions']);
        static::assertSame((new GoodsPriceRule())->getName(), $converted['cartRules'][0]['conditions'][2]['type']);
        static::assertSame((float) $promotionData[0]['minimumcharge'], $converted['cartRules'][0]['conditions'][2]['value']['amount']);
        static::assertSame('>=', $converted['cartRules'][0]['conditions'][2]['value']['operator']);
        static::assertSame((new AndRule())->getName(), $converted['cartRules'][0]['conditions'][1]['type']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedCustomerGroup(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['customergroup'] = '5';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertFalse($converted['discounts'][0]['considerAdvancedRules']);
        static::assertArrayNotHasKey('discountRules', $converted['discounts'][0]);
        static::assertArrayNotHasKey('cartRules', $converted);

        static::assertArrayHasKey('personaRules', $converted);
        static::assertCount(1, $converted['personaRules']);
        static::assertCount(3, $converted['personaRules'][0]['conditions']);
        static::assertSame((new CustomerGroupRule())->getName(), $converted['personaRules'][0]['conditions'][2]['type']);
        static::assertSame([$this->customerGroup], $converted['personaRules'][0]['conditions'][2]['value']['customerGroupIds']);
        static::assertSame('=', $converted['personaRules'][0]['conditions'][2]['value']['operator']);
        static::assertSame((new AndRule())->getName(), $converted['personaRules'][0]['conditions'][1]['type']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedCustomerGroupWithoutMapping(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['customergroup'] = '8';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNotNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertFalse($converted['discounts'][0]['considerAdvancedRules']);
        static::assertArrayNotHasKey('discountRules', $converted['discounts'][0]);
        static::assertArrayNotHasKey('cartRules', $converted);
        static::assertArrayNotHasKey('personaRules', $converted);

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);
        static::assertSame(DefaultEntities::CUSTOMER_GROUP, $logs[0]['parameters']['missingEntity']);
        static::assertSame(DefaultEntities::PROMOTION, $logs[0]['parameters']['requiredFor']);
        static::assertSame('8', $logs[0]['parameters']['sourceId']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithShippingDiscount(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['shippingfree'] = '1';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);
        static::assertArrayNotHasKey('cartRules', $converted);

        static::assertCount(2, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertFalse($converted['discounts'][0]['considerAdvancedRules']);
        static::assertArrayNotHasKey('discountRules', $converted['discounts'][0]);

        static::assertSame(100, $converted['discounts'][1]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_DELIVERY, $converted['discounts'][1]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][1]['type']);
        static::assertFalse($converted['discounts'][1]['considerAdvancedRules']);
        static::assertArrayNotHasKey('discountRules', $converted['discounts'][1]);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedProductsAndManufacturerAndMinimumPrice(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['restrictarticles'] = ';SW10002.1;SW10002.2;SW10002.3;';
        $promotionData[0]['bindtosupplier'] = '7';
        $promotionData[0]['minimumcharge'] = '25';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertFalse($converted['discounts'][0]['considerAdvancedRules']);
        static::assertArrayNotHasKey('discountRules', $converted['discounts']['0']);

        static::assertCount(1, $converted['cartRules']);
        static::assertCount(5, $converted['cartRules'][0]['conditions']);
        static::assertSame((new AndRule())->getName(), $converted['cartRules'][0]['conditions'][1]['type']);

        static::assertSame((new LineItemRule())->getName(), $converted['cartRules'][0]['conditions'][2]['type']);
        static::assertCount(3, $converted['cartRules'][0]['conditions'][2]['value']['identifiers']);
        static::assertSame(\array_values($this->restrictedProducts), $converted['cartRules'][0]['conditions'][2]['value']['identifiers']);

        static::assertSame((new LineItemOfManufacturerRule())->getName(), $converted['cartRules'][0]['conditions'][3]['type']);
        static::assertCount(1, $converted['cartRules'][0]['conditions'][3]['value']['manufacturerIds']);
        static::assertSame([$this->manufacturerId], $converted['cartRules'][0]['conditions'][3]['value']['manufacturerIds']);

        static::assertSame((new GoodsPriceRule())->getName(), $converted['cartRules'][0]['conditions'][4]['type']);
        static::assertSame(25.0, $converted['cartRules'][0]['conditions'][4]['value']['amount']);
        static::assertSame('>=', $converted['cartRules'][0]['conditions'][4]['value']['operator']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertWithRestrictedProductsAndManufacturerAndMinimumPriceWithDiscountRule(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';
        $promotionData[0]['restrictarticles'] = ';SW10002.1;SW10002.2;SW10002.3;';
        $promotionData[0]['bindtosupplier'] = '7';
        $promotionData[0]['minimumcharge'] = '25';
        $promotionData[0]['strict'] = 1;

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertSame($promotionData[0]['vouchercode'], $converted['code']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);

        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[0]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);
        static::assertTrue($converted['discounts'][0]['considerAdvancedRules']);
        static::assertCount(1, $converted['discounts'][0]['discountRules']);
        static::assertCount(4, $converted['discounts'][0]['discountRules'][0]['conditions']);
        static::assertSame((new OrRule())->getName(), $converted['discounts'][0]['discountRules'][0]['conditions'][1]['type']);

        static::assertSame((new LineItemRule())->getName(), $converted['discounts'][0]['discountRules'][0]['conditions'][2]['type']);
        static::assertSame(\array_values($this->restrictedProducts), $converted['discounts'][0]['discountRules'][0]['conditions'][2]['value']['identifiers']);

        static::assertSame((new LineItemOfManufacturerRule())->getName(), $converted['discounts'][0]['discountRules'][0]['conditions'][3]['type']);
        static::assertSame([$this->manufacturerId], $converted['discounts'][0]['discountRules'][0]['conditions'][3]['value']['manufacturerIds']);

        static::assertCount(1, $converted['cartRules']);
        static::assertCount(5, $converted['cartRules'][0]['conditions']);
        static::assertSame((new AndRule())->getName(), $converted['cartRules'][0]['conditions'][1]['type']);

        static::assertSame((new LineItemRule())->getName(), $converted['cartRules'][0]['conditions'][2]['type']);
        static::assertCount(3, $converted['cartRules'][0]['conditions'][2]['value']['identifiers']);
        static::assertSame(\array_values($this->restrictedProducts), $converted['cartRules'][0]['conditions'][2]['value']['identifiers']);

        static::assertSame((new LineItemOfManufacturerRule())->getName(), $converted['cartRules'][0]['conditions'][3]['type']);
        static::assertCount(1, $converted['cartRules'][0]['conditions'][3]['value']['manufacturerIds']);
        static::assertSame([$this->manufacturerId], $converted['cartRules'][0]['conditions'][3]['value']['manufacturerIds']);

        static::assertSame((new GoodsPriceRule())->getName(), $converted['cartRules'][0]['conditions'][4]['type']);
        static::assertSame(25.0, $converted['cartRules'][0]['conditions'][4]['value']['amount']);
        static::assertSame('>=', $converted['cartRules'][0]['conditions'][4]['value']['operator']);

        $this->assertSameTwiceMigration($promotionData[0], $context, $converted);
    }

    public function testConvertIndividualCodes(): void
    {
        $promotionData = require __DIR__ . '/../../../_fixtures/promotion_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($promotionData[1], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['active']);
        static::assertTrue($converted['useCodes']);
        static::assertCount(2, $converted['salesChannels']);
        static::assertCount(1, $converted['discounts']);
        static::assertSame((float) $promotionData[1]['value'], $converted['discounts'][0]['value']);
        static::assertSame(PromotionDiscountEntity::SCOPE_CART, $converted['discounts'][0]['scope']);
        static::assertSame(PromotionDiscountEntity::TYPE_PERCENTAGE, $converted['discounts'][0]['type']);

        static::assertTrue($converted['useIndividualCodes']);
        static::assertCount(4, $converted['individualCodes']);
        static::assertArrayNotHasKey('payload', $converted['individualCodes'][0]);
        static::assertSame($promotionData[1]['individualCodes'][0]['code'], $converted['individualCodes'][0]['code']);

        $firstname = $promotionData[1]['individualCodes'][1]['firstname'];
        $lastname = $promotionData[1]['individualCodes'][1]['lastname'];
        static::assertSame($firstname . ' ' . $lastname, $converted['individualCodes'][1]['payload']['customerName']);
        static::assertArrayNotHasKey('customerId', $converted['individualCodes'][1]['payload']);
        static::assertSame($promotionData[1]['individualCodes'][1]['code'], $converted['individualCodes'][1]['code']);

        $firstname = $promotionData[1]['individualCodes'][2]['firstname'];
        $lastname = $promotionData[1]['individualCodes'][2]['lastname'];
        static::assertSame($firstname . ' ' . $lastname, $converted['individualCodes'][2]['payload']['customerName']);
        static::assertSame($this->customer, $converted['individualCodes'][2]['payload']['customerId']);
        static::assertSame($promotionData[1]['individualCodes'][2]['code'], $converted['individualCodes'][2]['code']);

        $this->assertSameTwiceMigration($promotionData[1], $context, $converted);
    }

    private function assertSameTwiceMigration(array $promotionData, Context $context, ?array $converted): void
    {
        $convertResult = $this->converter->convert($promotionData, $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $secondTry = $convertResult->getConverted();
        static::assertSame($converted, $secondTry);
    }
}
