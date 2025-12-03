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
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LocaleLookup;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\BasicSettingsMappingService;

#[Package('fundamentals@after-sales')]
class LanguageConverterTest extends TestCase
{
    use KernelTestBehaviour;

    private MigrationContext $migrationContext;

    private Shopware55LanguageConverter $converter;

    private DummyLoggingService $loggingService;

    protected function setUp(): void
    {
        $this->loggingService = new DummyLoggingService();
        $this->converter = new Shopware55LanguageConverter(
            new BasicSettingsMappingService(),
            $this->loggingService,
            static::getContainer()->get(LocaleLookup::class),
            static::getContainer()->get(LanguageLookup::class),
        );

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
            null,
            new LanguageDataSet(),
            $runId,
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
        static::assertNotNull($convertResult);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertSame('Niederländisch', $converted['name']);
        static::assertSame($converted['translationCodeId'], $converted['localeId']);
    }
}
