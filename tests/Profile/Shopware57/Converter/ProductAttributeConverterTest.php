<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware57\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ProductAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Shopware57Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

#[Package('services-settings')]
class ProductAttributeConverterTest extends TestCase
{
    private Shopware57ProductAttributeConverter $converter;

    private MigrationContext $migrationContext;

    protected function setUp(): void
    {
        $mappingService = new DummyMappingService();
        $loggingService = new DummyLoggingService();
        $this->converter = new Shopware57ProductAttributeConverter($mappingService, $loggingService);

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setName('ConnectionName');
        $connection->setProfileName(Shopware57Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware57Profile(),
            $connection,
            $runId,
            new ProductAttributeDataSet(),
            0,
            250
        );
    }

    public function testConvertComboboxWereOptionsValueIsNull(): void
    {
        $comboboxAttribute = require __DIR__ . '/../../../_fixtures/attribute_data.php';
        $context = Context::createDefaultContext();

        $convertResult = $this->converter->convert($comboboxAttribute[10], $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('relations', $converted);
        static::assertSame('product', $converted['relations'][0]['entityName']);
        static::assertSame('migration_ConnectionName_product_combobox2', $converted['customFields'][0]['name']);
        static::assertSame('select', $converted['customFields'][0]['type']);
        static::assertSame('select', $converted['customFields'][0]['config']['customFieldType']);
        static::assertArrayHasKey('options', $converted['customFields'][0]['config']);
        static::assertSame([], $converted['customFields'][0]['config']['options']);
    }

    public function testConvertComboboxWereOptionsValueIsEmptyString(): void
    {
        $comboboxAttribute = require __DIR__ . '/../../../_fixtures/attribute_data.php';
        $context = Context::createDefaultContext();

        $convertResult = $this->converter->convert($comboboxAttribute[11], $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('relations', $converted);
        static::assertSame('product', $converted['relations'][0]['entityName']);
        static::assertSame('migration_ConnectionName_product_combobox3', $converted['customFields'][0]['name']);
        static::assertSame('select', $converted['customFields'][0]['type']);
        static::assertSame('select', $converted['customFields'][0]['config']['customFieldType']);
        static::assertArrayHasKey('options', $converted['customFields'][0]['config']);
        static::assertSame([], $converted['customFields'][0]['config']['options']);
    }
}
