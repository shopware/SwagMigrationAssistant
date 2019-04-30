<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductAttributeConverter;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class ProductAttributeConverterTest extends TestCase
{
    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var ProductAttributeConverter
     */
    private $converter;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->converter = new ProductAttributeConverter($mappingService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setProfile(new SwagMigrationProfileEntity());
        $this->connection->setId(Uuid::randomHex());

        $this->migrationContext = new MigrationContext(
            $this->connection,
            $this->runId,
            new ProductAttributeDataSet(),
            0,
            250
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->converter->supports(Shopware55Profile::PROFILE_NAME, new ProductAttributeDataSet());

        static::assertTrue($supportsDefinition);
    }

    public function testConvertAttribute(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/attribute_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($categoryData[0], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('relations', $converted);
        static::assertSame('product', $converted['relations'][0]['entityName']);
        static::assertSame('product_categorydate1', $converted['customFields'][0]['name']);
        static::assertSame('date', $converted['customFields'][0]['type']);
        static::assertSame('date', $converted['customFields'][0]['config']['type']);
        static::assertSame('date', $converted['customFields'][0]['config']['dateType']);
        static::assertSame('date', $converted['customFields'][0]['config']['customFieldType']);
    }
}
