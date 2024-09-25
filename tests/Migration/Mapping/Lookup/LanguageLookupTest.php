<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LocaleLookup;
use SwagMigrationAssistant\Test\Mock\ContextMock;

class LanguageLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $localeCode, ?string $expectedResult): void
    {
        $languageLookup = $this->getLanguageLookup();

        if ($expectedResult === null) {
            try {
                $languageLookup->get($localeCode, Context::createDefaultContext());
            } catch (MigrationException $e) {
                static::assertSame(
                    \sprintf('Locale with code: "%s" for language lookup not found.', $localeCode),
                    $e->getMessage()
                );

                static::assertSame(
                    'SWAG_MIGRATION__LOOKUP_LOCALE_FOR_LANGUAGE_LOOKUP_NOT_FOUND',
                    $e->getErrorCode()
                );
            }

            return;
        }

        static::assertSame($expectedResult, $languageLookup->get($localeCode, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $technicalName, ?string $expectedResult): void
    {
        $documentTypeLookup = $this->getMockedLanguageLookup();

        static::assertSame($expectedResult, $documentTypeLookup->get($technicalName, Context::createDefaultContext()));
    }

    #[DataProvider('getLanguageIdData')]
    public function testGetDefaultLanguageEntity(string $languageId, ?string $expectedResult): void
    {
        $languageLookup = $this->getLanguageLookup();

        $context = ContextMock::createDefaultContext();
        $context->setLangaugeIdChain([$languageId]);

        $result = $languageLookup->getLanguageEntity($context);

        if ($expectedResult === null) {
            static::assertNull($result);

            return;
        }

        static::assertInstanceOf(LanguageEntity::class, $result);
        static::assertSame($expectedResult, $result->getId());
    }

    #[DataProvider('getLanguageIdDatabaseData')]
    public function testGetDefaultLanguageEntityShouldGetDataFromCache(string $languageId, string $expectedResult): void
    {
        $languageLookup = $this->getMockedLanguageLookup();
        $context = ContextMock::createDefaultContext();
        $context->setLangaugeIdChain([$languageId]);

        $result = $languageLookup->getLanguageEntity($context);

        static::assertInstanceOf(LanguageEntity::class, $result);
        static::assertSame($expectedResult, $result->getId());
    }

    public function testReset(): void
    {
        $languageLookup = $this->getMockedLanguageLookup();

        $cacheProperty = new \ReflectionProperty(LanguageLookup::class, 'cache');
        $cacheProperty->setAccessible(true);
        static::assertNotEmpty($cacheProperty->getValue($languageLookup));

        $defaultLanguageCacheProperty = new \ReflectionProperty(LanguageLookup::class, 'defaultLanguageCache');
        $defaultLanguageCacheProperty->setAccessible(true);
        static::assertNotEmpty($defaultLanguageCacheProperty->getValue($languageLookup));

        $languageLookup->reset();

        static::assertEmpty($cacheProperty->getValue($languageLookup));
        static::assertEmpty($defaultLanguageCacheProperty->getValue($languageLookup));
    }

    /**
     * @return array<int, array{localeCode: string|null, expectedResult: ?string}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['localeCode' => 'FOO', 'expectedResult' => null];
        $returnData[] = ['localeCode' => 'BAR', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{localeCode: string|null, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('locale');
        $languageRepository = self::getContainer()->get('language.repository');
        $list = $languageRepository->search($criteria, Context::createDefaultContext())->getEntities();

        $returnData = [];
        foreach ($list as $language) {
            static::assertInstanceOf(LanguageEntity::class, $language);

            $returnData[] = [
                'localeCode' => $language->getLocale()?->getCode(),
                'expectedResult' => $language->getId(),
            ];
        }

        return $returnData;
    }

    /**
     * @return array<int, array{languageId: string, expectedResult: string|null}>
     */
    public static function getLanguageIdData(): array
    {
        $languageRepository = self::getContainer()->get('language.repository');
        $list = $languageRepository->search(new Criteria(), Context::createDefaultContext())->getEntities();

        $returnData = [];
        foreach ($list as $language) {
            static::assertInstanceOf(LanguageEntity::class, $language);

            $returnData[] = [
                'languageId' => $language->getId(),
                'expectedResult' => $language->getId(),
            ];
        }

        $returnData[] = [
            'languageId' => Uuid::randomHex(),
            'expectedResult' => null,
        ];

        $returnData[] = [
            'languageId' => Uuid::randomHex(),
            'expectedResult' => null,
        ];

        return $returnData;
    }

    /**
     * @return array<int, array{languageId: string, expectedResult: string}>
     */
    public static function getLanguageIdDatabaseData(): array
    {
        $languageRepository = self::getContainer()->get('language.repository');
        $list = $languageRepository->search(new Criteria(), Context::createDefaultContext())->getEntities();

        $returnData = [];
        foreach ($list as $language) {
            static::assertInstanceOf(LanguageEntity::class, $language);

            $returnData[] = [
                'languageId' => $language->getId(),
                'expectedResult' => $language->getId(),
            ];
        }

        return $returnData;
    }

    private function getLanguageLookup(): LanguageLookup
    {
        return new LanguageLookup(
            $this->getContainer()->get('language.repository'),
            new LocaleLookup(
                $this->getContainer()->get('locale.repository')
            )
        );
    }

    private function getMockedLanguageLookup(): LanguageLookup
    {
        $languageRepository = $this->createMock(EntityRepository::class);
        $languageRepository->method('search')->willThrowException(
            new \Exception('LanguageLookup repository should not be called')
        );

        $documentTypeLookup = new LanguageLookup($languageRepository, new LocaleLookup($languageRepository));

        $reflectionCacheProperty = new \ReflectionProperty(LanguageLookup::class, 'cache');
        $reflectionCacheProperty->setAccessible(true);
        $reflectionCacheProperty->setValue($documentTypeLookup, $this->getCacheData());

        $reflectionLanguageCacheProperty = new \ReflectionProperty(LanguageLookup::class, 'defaultLanguageCache');
        $reflectionLanguageCacheProperty->setAccessible(true);
        $reflectionLanguageCacheProperty->setValue($documentTypeLookup, $this->getDefaultLanguageCacheData());

        return $documentTypeLookup;
    }

    /**
     * @return array<string, string>
     */
    private function getCacheData(): array
    {
        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[$data['localeCode']] = $data['expectedResult'];
        }

        return $cacheData;
    }

    /**
     * @return array<string, LanguageEntity>
     */
    public function getDefaultLanguageCacheData(): array
    {
        $languageRepository = self::getContainer()->get('language.repository');
        $list = $languageRepository->search(new Criteria(), Context::createDefaultContext())->getEntities();

        $defaultLanguageCacheData = [];
        foreach ($list as $language) {
            static::assertInstanceOf(LanguageEntity::class, $language);
            $defaultLanguageCacheData[$language->getId()] = $language;
        }

        return $defaultLanguageCacheData;
    }
}
