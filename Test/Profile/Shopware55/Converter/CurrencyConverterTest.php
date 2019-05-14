<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationAssistant\Profile\Shopware55\Converter\CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\BasicSettingsMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\CurrencyMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class CurrencyConverterTest extends TestCase
{
    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var CurrencyConverter
     */
    private $converter;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var BasicSettingsMappingService
     */
    private $mappingService;

    protected function setUp(): void
    {
        $this->mappingService = new CurrencyMappingService();
        $this->context = Context::createDefaultContext();
        $this->loggingService = new DummyLoggingService();
        $this->converter = new CurrencyConverter($this->mappingService, $this->loggingService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setProfile(new SwagMigrationProfileEntity());
        $this->connection->setId(Uuid::randomHex());

        $this->migrationContext = new MigrationContext(
            $this->connection,
            $this->runId,
            new CurrencyDataSet(),
            0,
            250
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->converter->supports(Shopware55Profile::PROFILE_NAME, new CurrencyDataSet());

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $currencyData = require __DIR__ . '/../../../_fixtures/currency_data.php';
        $convertResult = $this->converter->convert($currencyData[0], $this->context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        $defaultLanguage = BasicSettingsMappingService::DEFAULT_LANGUAGE_UUID;

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('translations', $converted);
        static::assertSame('COC', $converted['translations'][$defaultLanguage]['shortName']);
        static::assertSame('Kekse', $converted['translations'][$defaultLanguage]['name']);
        static::assertSame($defaultLanguage, $converted['translations'][$defaultLanguage]['languageId']);

        static::assertSame('COC', $converted['shortName']);
        static::assertSame('COC', $converted['isoCode']);
        static::assertSame('Kekse', $converted['name']);
        static::assertFalse($converted['isDefault']);
        static::assertSame(100, $converted['factor']);
        static::assertSame('COOCIES', $converted['symbol']);
        static::assertFalse($converted['placedInFront']);
        static::assertSame(0, $converted['position']);
        static::assertSame($this->context->getCurrencyPrecision(), $converted['decimalPrecision']);
    }

    public function testConvertWhichExists(): void
    {
        $this->converter = new CurrencyConverter(new DummyMappingService(), $this->loggingService);
        $currencyData = require __DIR__ . '/../../../_fixtures/currency_data.php';
        $convertResult = $this->converter->convert($currencyData[0], $this->context, $this->migrationContext);

        static::assertNull($convertResult->getConverted());
        static::assertNotNull($convertResult->getUnmapped());

        $logs = $this->loggingService->getLoggingArray();
        static::assertEmpty($logs);
    }
}
