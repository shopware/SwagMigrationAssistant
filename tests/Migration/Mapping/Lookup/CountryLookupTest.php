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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\Country\CountryEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryLookup;

class CountryLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getIso2TestData')]
    public function testGetByIso2(string $iso2, ?string $expectedResult): void
    {
        $countryLookup = $this->getCountryLookup();

        static::assertSame($expectedResult, $countryLookup->getByIso2($iso2, Context::createDefaultContext()));
    }

    #[DataProvider('getIso3TestData')]
    public function testGetByIso3(string $iso3, ?string $expectedResult): void
    {
        $countryLookup = $this->getCountryLookup();

        static::assertSame($expectedResult, $countryLookup->getByIso3($iso3, Context::createDefaultContext()));
    }

    #[DataProvider('getIso3DatabaseData')]
    public function testGetByIso2ShouldGetDataFromCache(string $iso3, ?string $expectedResult): void
    {
        $countryLookup = $this->getMockedCountryLookup();

        static::assertSame($expectedResult, $countryLookup->getByIso2($iso3, Context::createDefaultContext()));
    }

    #[DataProvider('getIso3DatabaseData')]
    public function testGetByIso3ShouldGetDataFromCache(string $iso3, ?string $expectedResult): void
    {
        $countryLookup = $this->getMockedCountryLookup();

        static::assertSame($expectedResult, $countryLookup->getByIso3($iso3, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $countryLookup = $this->getMockedCountryLookup();

        $cacheProperty = new \ReflectionProperty(CountryLookup::class, 'cache');
        $cacheProperty->setAccessible(true);
        static::assertNotEmpty($cacheProperty->getValue($countryLookup));

        $countryLookup->reset();

        static::assertEmpty($cacheProperty->getValue($countryLookup));
    }

    /**
     * @return array<int, array{iso2: string|null, expectedResult: string|null}>
     */
    public static function getIso2TestData(): array
    {
        $returnData = self::getIso2DatabaseData();
        $returnData[] = ['iso2' => 'FOO', 'expectedResult' => null];
        $returnData[] = ['iso2' => 'FOO', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{iso3: string|null, expectedResult: string|null}>
     */
    public static function getIso3TestData(): array
    {
        $returnData = self::getIso3DatabaseData();
        $returnData[] = ['iso3' => 'FOO', 'expectedResult' => null];
        $returnData[] = ['iso3' => 'FOO', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{iso2: string|null, expectedResult: string|null}>
     */
    public static function getIso2DatabaseData(): array
    {
        $databaseData = self::getDatabaseData();

        return \array_map(static function (array $data): array {
            return [
                'iso2' => $data['iso2'],
                'expectedResult' => $data['expectedResult'],
            ];
        }, $databaseData);
    }

    /**
     * @return array<int, array{iso3: string|null, expectedResult: string|null}>
     */
    public static function getIso3DatabaseData(): array
    {
        $databaseData = self::getDatabaseData();

        return \array_map(static function (array $data): array {
            return [
                'iso3' => $data['iso3'],
                'expectedResult' => $data['expectedResult'],
            ];
        }, $databaseData);
    }

    /**
     * @return array<int, array{iso2: string|null, iso3: string|null, expectedResult: string|null}>
     */
    private static function getDatabaseData(): array
    {
        $countryRepository = self::getContainer()->get('country.repository');
        $criteria = new Criteria();
        $criteria->setLimit(20);
        $criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
        $list = $countryRepository->search($criteria, Context::createDefaultContext())->getEntities();

        $returnData = [];
        foreach ($list as $country) {
            static::assertInstanceOf(CountryEntity::class, $country);

            $returnData[] = [
                'iso2' => $country->getIso(),
                'iso3' => $country->getIso3(),
                'expectedResult' => $country->getId(),
            ];
        }

        return $returnData;
    }

    private function getCountryLookup(): CountryLookup
    {
        $countryLookup = $this->getContainer()->get(CountryLookup::class);
        static::assertInstanceOf(CountryLookup::class, $countryLookup);

        return $countryLookup;
    }

    private function getMockedCountryLookup(): CountryLookup
    {
        $currencyRepository = $this->createMock(EntityRepository::class);
        $currencyRepository->method('search')->willThrowException(
            new \Exception('CountryLookup repository should not be called')
        );
        $countryLookup = new CountryLookup($currencyRepository);

        $reflectionProperty = new \ReflectionProperty(CountryLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[$data['iso3']] = $data['expectedResult'];
            $cacheData[$data['iso2']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($countryLookup, $cacheData);

        return $countryLookup;
    }
}
