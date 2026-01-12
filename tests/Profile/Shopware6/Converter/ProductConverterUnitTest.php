<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Converter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\StringType;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ProductConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\Dummy6MappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;

#[Package('fundamentals@after-sales')]
class ProductConverterUnitTest extends TestCase
{
    public function testConvertStatesToTypeWithDownloadState(): void
    {
        $converter = $this->createConverterWithTypeColumn(true);
        $context = Context::createDefaultContext();
        $migrationContext = $this->createMigrationContext();

        $input = [
            'id' => Uuid::randomHex(),
            'productNumber' => 'TEST-001',
            'stock' => 10,
            'states' => ['is-download'],
            'name' => 'Digital Product',
        ];

        $result = $converter->convert($input, $context, $migrationContext);
        $converted = $result->getConverted();

        static::assertNotNull($converted);
        static::assertArrayHasKey('type', $converted);
        static::assertSame(ProductDefinition::TYPE_DIGITAL, $converted['type']);
    }

    public function testConvertStatesToTypeWithPhysicalState(): void
    {
        $converter = $this->createConverterWithTypeColumn(true);
        $context = Context::createDefaultContext();
        $migrationContext = $this->createMigrationContext();

        $input = [
            'id' => Uuid::randomHex(),
            'productNumber' => 'TEST-002',
            'stock' => 10,
            'states' => ['is-physical'],
            'name' => 'Physical Product',
        ];

        $result = $converter->convert($input, $context, $migrationContext);
        $converted = $result->getConverted();

        static::assertNotNull($converted);
        static::assertArrayHasKey('type', $converted);
        static::assertSame(ProductDefinition::TYPE_PHYSICAL, $converted['type']);
    }

    public function testConvertPreservesExistingType(): void
    {
        $converter = $this->createConverterWithTypeColumn(true);
        $context = Context::createDefaultContext();
        $migrationContext = $this->createMigrationContext();

        $input = [
            'id' => Uuid::randomHex(),
            'productNumber' => 'TEST-003',
            'stock' => 10,
            'type' => 'digital',
            'name' => 'Product With Type',
        ];

        $result = $converter->convert($input, $context, $migrationContext);
        $converted = $result->getConverted();

        static::assertNotNull($converted);
        static::assertArrayHasKey('type', $converted);
        static::assertSame('digital', $converted['type']);
    }

    public function testConvertDefaultsToPhysicalType(): void
    {
        $converter = $this->createConverterWithTypeColumn(true);
        $context = Context::createDefaultContext();
        $migrationContext = $this->createMigrationContext();

        $input = [
            'id' => Uuid::randomHex(),
            'productNumber' => 'TEST-004',
            'stock' => 10,
            'name' => 'Product Without Type Or States',
        ];

        $result = $converter->convert($input, $context, $migrationContext);
        $converted = $result->getConverted();

        static::assertNotNull($converted);
        static::assertArrayHasKey('type', $converted);
        static::assertSame(ProductDefinition::TYPE_PHYSICAL, $converted['type']);
    }

    public function testConvertDoesNotSetTypeWhenColumnDoesNotExist(): void
    {
        $converter = $this->createConverterWithTypeColumn(false);
        $context = Context::createDefaultContext();
        $migrationContext = $this->createMigrationContext();

        $input = [
            'id' => Uuid::randomHex(),
            'productNumber' => 'TEST-005',
            'stock' => 10,
            'states' => ['is-download'],
            'name' => 'Product On Old Database',
        ];

        $result = $converter->convert($input, $context, $migrationContext);
        $converted = $result->getConverted();

        static::assertNotNull($converted);
        static::assertArrayNotHasKey('type', $converted);
    }

    private function createConverterWithTypeColumn(bool $hasTypeColumn): ProductConverter
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        if ($hasTypeColumn) {
            $schemaManager->method('listTableColumns')->willReturn([
                'type' => new Column('type', new StringType()),
            ]);
        } else {
            $schemaManager->method('listTableColumns')->willReturn([]);
        }

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        return new ProductConverter(
            new Dummy6MappingService(),
            new DummyLoggingService(),
            new DummyMediaFileService(),
            $connection
        );
    }

    private function createMigrationContext(): MigrationContext
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware6MajorProfile::PROFILE_NAME);

        return new MigrationContext(
            new Shopware6MajorProfile('6.5'),
            $connection,
            Uuid::randomHex(),
            new ProductDataSet(),
            0,
            250
        );
    }
}
