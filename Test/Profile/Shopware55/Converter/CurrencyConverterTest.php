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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CurrencyConverter;
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
     * @var Shopware55CurrencyConverter
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
        $this->converter = new Shopware55CurrencyConverter($this->mappingService, $this->loggingService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new CurrencyDataSet(),
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
        $currencyData = require __DIR__ . '/../../../_fixtures/currency_data.php';
        $convertResult = $this->converter->convert($currencyData[0], $this->context, $this->migrationContext);
        $this->converter->writeMapping($this->context);
        $converted = $convertResult->getConverted();
        $defaultLanguage = BasicSettingsMappingService::DEFAULT_LANGUAGE_UUID;

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('translations', $converted);
        static::assertSame('COC', $converted['translations'][$defaultLanguage]['shortName']);
        static::assertSame('Kekse', $converted['translations'][$defaultLanguage]['name']);
        static::assertSame($defaultLanguage, $converted['translations'][$defaultLanguage]['languageId']);

        static::assertSame('COC', $converted['shortName']);
        static::assertSame('COC', $converted['isoCode']);
        static::assertSame('Kekse', $converted['name']);
        static::assertFalse($converted['isDefault']);
        static::assertSame(100.0, $converted['factor']);
        static::assertSame('COOCIES', $converted['symbol']);
        static::assertFalse($converted['placedInFront']);
        static::assertSame(0, $converted['position']);
        static::assertArrayHasKey('itemRounding', $converted);
        static::assertArrayHasKey('totalRounding', $converted);
        static::assertSame($this->context->getRounding()->getDecimals(), $converted['itemRounding']['decimals']);
        static::assertSame($this->context->getRounding()->getDecimals(), $converted['totalRounding']['decimals']);
    }

    public function testConvertWhichExists(): void
    {
        $this->converter = new Shopware55CurrencyConverter(new DummyMappingService(), $this->loggingService);
        $currencyData = require __DIR__ . '/../../../_fixtures/currency_data.php';
        $convertResult = $this->converter->convert($currencyData[0], $this->context, $this->migrationContext);

        static::assertNull($convertResult->getConverted());
        static::assertNotNull($convertResult->getUnmapped());

        $logs = $this->loggingService->getLoggingArray();
        static::assertEmpty($logs);
    }
}
