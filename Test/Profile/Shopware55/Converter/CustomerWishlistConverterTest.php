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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerWishlistDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerWishlistConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

#[Package('services-settings')]
class CustomerWishlistConverterTest extends TestCase
{
    private MigrationContext $migrationContext;

    private Shopware55CustomerWishlistConverter $converter;

    private Context $context;

    protected function setUp(): void
    {
        $mappingService = new DummyMappingService();
        $this->converter = new Shopware55CustomerWishlistConverter($mappingService, new DummyLoggingService());
        $this->context = Context::createDefaultContext();

        $runId = Uuid::randomHex();
        $connectionId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId($connectionId);
        $connection->setName('ConnectionName');
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new CustomerWishlistDataSet(),
            0,
            250
        );

        $mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::CUSTOMER,
            '3',
            $this->context,
            null,
            [],
            Uuid::randomHex()
        );

        $mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::PRODUCT,
            'SW10002.3',
            $this->context,
            null,
            [],
            Uuid::randomHex()
        );

        $mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::SALES_CHANNEL,
            '1',
            $this->context,
            null,
            [],
            Uuid::randomHex()
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->converter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/customer_wishlist.php';

        $convertResult = $this->converter->convert($data[0], $this->context, $this->migrationContext);
        $this->converter->writeMapping($this->context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
    }

    public function testConvertWithoutCustomer(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/customer_wishlist.php';
        $data[0]['userID'] = '99';

        $convertResult = $this->converter->convert($data[0], $this->context, $this->migrationContext);
        $this->converter->writeMapping($this->context);

        static::assertNull($convertResult->getConverted());
        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($convertResult->getMappingUuid());
    }

    public function testConvertWithoutProduct(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/customer_wishlist.php';
        $data[0]['ordernumber'] = '99';

        $convertResult = $this->converter->convert($data[0], $this->context, $this->migrationContext);
        $this->converter->writeMapping($this->context);

        static::assertNull($convertResult->getConverted());
        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($convertResult->getMappingUuid());
    }

    public function testConvertWithoutSalesChannel(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/customer_wishlist.php';
        $data[0]['subshopID'] = '99';

        $convertResult = $this->converter->convert($data[0], $this->context, $this->migrationContext);
        $this->converter->writeMapping($this->context);

        static::assertNull($convertResult->getConverted());
        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($convertResult->getMappingUuid());
    }
}
