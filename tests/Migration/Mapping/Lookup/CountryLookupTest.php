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
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryLookup;

class CountryLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getTestData')]
    public function testGet(string $iso, string $iso3, ?string $expectedResult): void
    {
        $countryLookup = $this->getCountryLookup();

        static::assertSame($expectedResult, $countryLookup->get($iso, $iso3, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $iso, string $iso3, ?string $expectedResult): void
    {
        $countryLookup = $this->getMockedCountryLookup();

        static::assertSame($expectedResult, $countryLookup->get($iso, $iso3, Context::createDefaultContext()));
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
     * @return array<int, array{iso: string, iso3: string, expectedResult: ?string}>
     */
    public static function getTestData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['iso' => 'FO', 'iso3' => 'FOO', 'expectedResult' => null];
        $returnData[] = ['iso' => 'FO', 'iso3' => 'FOO', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{iso: string, iso3: string, expectedResult: ?string}>
     */
    public static function getDatabaseData(): array
    {
        $countryRepository = self::getContainer()->get('country.repository');
        $list = $countryRepository->search(new Criteria(), Context::createDefaultContext())->getEntities();

        $returnData = [];
        foreach ($list as $country) {
            $returnData[] = [
                'iso' => $country->getIso(),
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
            $cacheData[\sprintf('%s-%s', $data['iso'], $data['iso3'])] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($countryLookup, $cacheData);

        return $countryLookup;
    }
}
