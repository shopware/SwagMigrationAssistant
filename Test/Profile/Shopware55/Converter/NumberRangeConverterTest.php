<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware55\Converter\NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class NumberRangeConverterTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var NumberRangeConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $numberRangeRepo = $this->getContainer()->get('number_range_type.repository');
        $mappingService = new DummyMappingService();
        $loggingService = new DummyLoggingService();
        $this->converter = new NumberRangeConverter($mappingService, $numberRangeRepo, $loggingService);

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());

        $this->migrationContext = new MigrationContext(
            $connection,
            $runId,
            new NumberRangeDataSet(),
            0,
            250
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->converter->supports(Shopware55Profile::PROFILE_NAME, new NumberRangeDataSet());

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $numberRangeData = require __DIR__ . '/../../../_fixtures/number_range_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($numberRangeData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['global']);
        static::assertSame('SW{n}', $converted['pattern']);
        static::assertSame(10002, $converted['start']);

        $convertResult = $this->converter->convert($numberRangeData[1], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);

        static::assertFalse($converted['global']);
        static::assertSame('SW{n}', $converted['pattern']);
        static::assertSame(20006, $converted['start']);
    }

    public function testConvertWithUnknownType(): void
    {
        $numberRangeData = require __DIR__ . '/../../../_fixtures/number_range_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($numberRangeData[2], $context, $this->migrationContext);
        $this->converter->writeMapping($context);

        static::assertNull($convertResult->getConverted());
        static::assertNotNull($convertResult->getUnmapped());
    }
}
