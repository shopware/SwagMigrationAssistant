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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\BasicSettingsMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class LanguageConverterTest extends TestCase
{
    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var Shopware55LanguageConverter
     */
    private $converter;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    protected function setUp(): void
    {
        $this->loggingService = new DummyLoggingService();
        $this->converter = new Shopware55LanguageConverter(new BasicSettingsMappingService(), $this->loggingService);

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new LanguageDataSet(),
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
        $languageData = require __DIR__ . '/../../../_fixtures/language_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($languageData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertSame('Niederländisch', $converted['name']);
        static::assertSame($converted['translationCodeId'], $converted['localeId']);
    }

    public function testConvertWhichExists(): void
    {
        $languageData = require __DIR__ . '/../../../_fixtures/language_data.php';

        $context = Context::createDefaultContext();
        $this->converter = new Shopware55LanguageConverter(new DummyMappingService(), $this->loggingService);
        $convertResult = $this->converter->convert($languageData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);

        static::assertNull($convertResult->getConverted());
        static::assertNotNull($convertResult->getUnmapped());

        $logs = $this->loggingService->getLoggingArray();
        static::assertSame('SWAG_MIGRATION_LANGUAGE_ENTITY_ALREADY_EXISTS', $logs[0]['code']);
    }
}
