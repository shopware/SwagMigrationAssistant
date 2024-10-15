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
use Shopware\Core\System\Currency\CurrencyEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CurrencyLookup;

class CurrencyLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $isoCode, ?string $expectedResult): void
    {
        $currencyLookup = $this->getCurrencyLookup();

        static::assertSame($expectedResult, $currencyLookup->get($isoCode, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $isoCode, ?string $expectedResult): void
    {
        $currencyLookup = $this->getMockedCountryLookup();

        static::assertSame($expectedResult, $currencyLookup->get($isoCode, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $currencyLookup = $this->getMockedCountryLookup();

        $cacheProperty = new \ReflectionProperty(CurrencyLookup::class, 'cache');
        $cacheProperty->setAccessible(true);
        static::assertNotEmpty($cacheProperty->getValue($currencyLookup));

        $currencyLookup->reset();

        static::assertEmpty($cacheProperty->getValue($currencyLookup));
    }

    /**
     * @return array<int, array{isoCode: string, expectedResult: ?string}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();

        $returnData[] = ['isoCode' => 'FOO', 'expectedResult' => null];
        $returnData[] = ['isoCode' => 'BAR', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{isoCode: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $currencyRepository = self::getContainer()->get('currency.repository');
        $list = $currencyRepository->search(new Criteria(), Context::createDefaultContext())->getEntities();

        $returnData = [];
        foreach ($list as $currency) {
            static::assertInstanceOf(CurrencyEntity::class, $currency);

            $returnData[] = [
                'isoCode' => $currency->getIsoCode(),
                'expectedResult' => $currency->getId(),
            ];
        }

        return $returnData;
    }

    private function getCurrencyLookup(): CurrencyLookup
    {
        $currencyLookup = $this->getContainer()->get(CurrencyLookup::class);
        static::assertInstanceOf(CurrencyLookup::class, $currencyLookup);

        return $currencyLookup;
    }

    private function getMockedCountryLookup(): CurrencyLookup
    {
        $currencyRepository = $this->createMock(EntityRepository::class);
        $currencyRepository->method('search')->willThrowException(
            new \Exception('CurrencyLookup repository should not be called')
        );
        $currencyLookup = new CurrencyLookup($currencyRepository);

        $reflectionProperty = new \ReflectionProperty(CurrencyLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[$data['isoCode']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($currencyLookup, $cacheData);

        return $currencyLookup;
    }
}
