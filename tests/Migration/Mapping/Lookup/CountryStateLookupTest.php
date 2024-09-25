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
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryStateLookup;

class CountryStateLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $countryIso, string $countryStateCode, ?string $expectedResult): void
    {
        $countryStateLookup = $this->getCountryStateLookup();

        static::assertSame(
            $expectedResult,
            $countryStateLookup->get($countryIso, $countryStateCode, Context::createDefaultContext())
        );
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $countryIso, string $countryStateCode, ?string $expectedResult): void
    {
        $countryStateLookup = $this->getMockedCountryStateLookup();

        static::assertSame(
            $expectedResult,
            $countryStateLookup->get($countryIso, $countryStateCode, Context::createDefaultContext())
        );
    }

    public function testReset(): void
    {
        $countryStateLookup = $this->getMockedCountryStateLookup();

        $cacheProperty = new \ReflectionProperty(CountryStateLookup::class, 'cache');
        $cacheProperty->setAccessible(true);
        static::assertNotEmpty($cacheProperty->getValue($countryStateLookup));

        $countryStateLookup->reset();

        static::assertEmpty($cacheProperty->getValue($countryStateLookup));
    }

    /**
     * @return array<int, array{countryIso: string|null, countryStateCode: string, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['countryIso' => 'FO', 'countryStateCode' => 'FOO', 'expectedResult' => null];
        $returnData[] = ['countryIso' => 'BA', 'countryStateCode' => 'BAR', 'expectedResult' => null];
        $returnData[] = ['countryIso' => 'BZ', 'countryStateCode' => 'BAZ', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{countryIso: string|null, countryStateCode: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $countryStateRepository = static::getContainer()->get('country_state.repository');

        $criteria = new Criteria();
        $criteria->addAssociation('country');
        $list = $countryStateRepository->search($criteria, Context::createDefaultContext())->getEntities();

        $returnData = [];
        foreach ($list as $countryState) {
            static::assertInstanceOf(CountryStateEntity::class, $countryState);
            $countryIso = $countryState->getCountry()?->getIso();

            $returnData[] = [
                'countryIso' => $countryIso,
                'countryStateCode' => \str_replace($countryIso . '-', '', $countryState->getShortCode()),
                'expectedResult' => $countryState->getId(),
            ];
        }

        return $returnData;
    }

    private function getCountryStateLookup(): CountryStateLookup
    {
        $countryStateLookup = $this->getContainer()->get(CountryStateLookup::class);
        static::assertInstanceOf(CountryStateLookup::class, $countryStateLookup);

        return $countryStateLookup;
    }

    private function getMockedCountryStateLookup(): CountryStateLookup
    {
        $countryStateRepository = $this->createMock(EntityRepository::class);
        $countryStateRepository->method('searchIds')->willThrowException(
            new \Exception('CountryStateLookup repository should not be called')
        );

        $countryStateLookup = new CountryStateLookup($countryStateRepository);

        $reflectionProperty = new \ReflectionProperty(CountryStateLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[\sprintf('%s-%s', $data['countryIso'], $data['countryStateCode'])] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($countryStateLookup, $cacheData);

        return $countryStateLookup;
    }
}
