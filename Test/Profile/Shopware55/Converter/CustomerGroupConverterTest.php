<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerGroupConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class CustomerGroupConverterTest extends TestCase
{
    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var Shopware55CustomerGroupConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $mappingService = new DummyMappingService();
        $this->converter = new Shopware55CustomerGroupConverter($mappingService, new DummyLoggingService());

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setName('ConnectionName');
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new CustomerGroupDataSet(),
            0,
            250
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->converter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $customerGroupData = require __DIR__ . '/../../../_fixtures/customer_group_data.php';

        $defaultLanguage = DummyMappingService::DEFAULT_LANGUAGE_UUID;
        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($customerGroupData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertSame('Händler', $converted['name']);
        static::assertFalse($converted['displayGross']);
        static::assertFalse($converted['inputGross']);
        static::assertFalse($converted['hasGlobalDiscount']);
        static::assertSame(100.0, $converted['minimumOrderAmount']);
        static::assertSame(1000.0, $converted['minimumOrderAmountSurcharge']);
        static::assertSame($defaultLanguage, $converted['translations'][$defaultLanguage]['languageId']);
        static::assertSame('Händler', $converted['translations'][$defaultLanguage]['name']);
    }
}
