<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware6\Converter\LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\Dummy6MappingService;

#[Package('services-settings')]
class LanguageConverterConvertDataTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testConvertData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = $this->createMigrationContext();

        $languageConverter = $this->createLanguageConverter();
        $languageId = Uuid::randomHex();

        $data = [
            'localeId' => '018ebc2838d871b883e3fe94aa050371',
            'translationCodeId' => '018ebc2838d871b883e3fe94aa050371',
            'name' => 'Deutsch',
            'locale' => [
                'code' => 'en-US',
                'name' => 'English',
                'territory' => 'United States',
                'id' => '018ebc2838d871b883e3fe94aa050371',
            ],
            'id' => $languageId,
        ];

        $result = $languageConverter->convert($data, $context, $migrationContext);
        $converted = $result->getConverted() ?? [];

        static::assertArrayHasKey('id', $converted);
        static::assertSame($languageId, $converted['id']);
    }

    public function testConvertDataShouldNotOverwriteTheDefaultLanguage(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = $this->createMigrationContext();

        $languageConverter = $this->createLanguageConverter();
        $defaultLanguageId = Defaults::LANGUAGE_SYSTEM;

        $data = [
            'localeId' => '018ebc2838d871b883e3fe94aa050371',
            'translationCodeId' => '018ebc2838d871b883e3fe94aa050371',
            'name' => 'Deutsch',
            'locale' => [
                'code' => 'en-US',
                'name' => 'English',
                'territory' => 'United States',
                'id' => '018ebc2838d871b883e3fe94aa050371',
            ],
            'id' => $defaultLanguageId,
        ];

        $result = $languageConverter->convert($data, $context, $migrationContext);
        $converted = $result->getConverted() ?? [];

        static::assertArrayHasKey('id', $converted);
        static::assertNotSame($defaultLanguageId, $converted['id']);
    }

    public function testCheckDataForDefaultLanguageShouldReturnDataUnchangedWitNoDefaultLanguage(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = $this->createMigrationContext();
        $mappingServiceMock = $this->createMock(Shopware6MappingServiceInterface::class);
        $mappingServiceMock->method('getDefaultLanguage')->willReturn(null);

        $expectedId = 'testID';

        $data = [
            'id' => $expectedId,
            'locale' => [
                'code' => 'en-US',
            ],
        ];

        $languageConverter = $this->createLanguageConverter($mappingServiceMock);
        $result = $languageConverter->convert($data, $context, $migrationContext);
        $converted = $result->getConverted() ?? [];

        static::assertArrayHasKey('id', $converted);
        static::assertSame($expectedId, $converted['id']);
    }

    public function testCheckDataForDefaultLanguageShouldReturnDataUnchangedWitNoDefaultLocale(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = $this->createMigrationContext();

        $mappingServiceMock = $this->createMock(Shopware6MappingServiceInterface::class);
        $mappingServiceMock->method('getDefaultLanguage')->willReturn(new LanguageEntity());

        $expectedId = 'testID';

        $data = [
            'id' => $expectedId,
            'locale' => [
                'code' => 'en-US',
            ],
        ];

        $languageConverter = $this->createLanguageConverter($mappingServiceMock);
        $result = $languageConverter->convert($data, $context, $migrationContext);
        $converted = $result->getConverted() ?? [];

        static::assertArrayHasKey('id', $converted);
        static::assertSame($expectedId, $converted['id']);
    }

    private function createLanguageConverter(
        ?Shopware6MappingServiceInterface $mappingService = null,
        ?LoggingServiceInterface $loggingService = null,
    ): LanguageConverter {
        if ($mappingService === null) {
            $mappingService = new Dummy6MappingService();
        }

        if ($loggingService === null) {
            $loggingService = $this->createMock(LoggingServiceInterface::class);
        }

        return new LanguageConverter($mappingService, $loggingService);
    }

    private function createMigrationContext(): MigrationContext
    {
        $migrationConnectionEntity = new SwagMigrationConnectionEntity();
        $migrationConnectionEntity->setId(Uuid::randomHex());

        return new MigrationContext(
            new Shopware6MajorProfile('6.6.0.0'),
            $migrationConnectionEntity,
            Uuid::randomHex(),
            new LanguageDataSet(),
            0,
            10
        );
    }
}
