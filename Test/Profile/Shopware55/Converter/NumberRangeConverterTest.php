<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55NumberRangeConverter;
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
     * @var Shopware55NumberRangeConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $numberRangeRepo = $this->getContainer()->get('number_range_type.repository');
        $mappingService = new DummyMappingService();
        $loggingService = new DummyLoggingService();
        $this->converter = new Shopware55NumberRangeConverter($mappingService, $loggingService, $numberRangeRepo);

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new NumberRangeDataSet(),
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
        $numberRangeData = require __DIR__ . '/../../../_fixtures/number_range_data.php';
        $context = Context::createDefaultContext();

        // Artikelbestellnummer
        $convertResult = $this->converter->convert($numberRangeData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);

        static::assertTrue($converted['global']);
        static::assertSame('SW{n}', $converted['pattern']);
        static::assertSame(10002, $converted['start']);

        // Kunden
        $convertResult = $this->converter->convert($numberRangeData[1], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);

        static::assertFalse($converted['global']);
        static::assertSame('SW{n}', $converted['pattern']);
        static::assertSame(20006, $converted['start']);

        // Rechnungen
        $convertResult = $this->converter->convert($numberRangeData[3], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);

        static::assertFalse($converted['global']);
        static::assertSame('SW{n}', $converted['pattern']);
        static::assertSame(30006, $converted['start']);

        // Lieferscheine
        $convertResult = $this->converter->convert($numberRangeData[4], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);

        static::assertFalse($converted['global']);
        static::assertSame('SW{n}', $converted['pattern']);
        static::assertSame(40006, $converted['start']);

        // Gutschriften
        $convertResult = $this->converter->convert($numberRangeData[5], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);

        static::assertFalse($converted['global']);
        static::assertSame('SW{n}', $converted['pattern']);
        static::assertSame(50006, $converted['start']);
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
