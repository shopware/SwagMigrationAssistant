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
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tax\TaxEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxLookup;

class TaxLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(float $taxRate, ?string $expectedResult): void
    {
        $taxLookup = $this->getTaxLookup();

        static::assertSame($expectedResult, $taxLookup->get($taxRate, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(float $taxRate, string $expectedResult): void
    {
        $taxLookup = $this->getMockedTaxLookup();

        static::assertSame($expectedResult, $taxLookup->get($taxRate, Context::createDefaultContext()));
    }

    #[DataProvider('getGetByTaxRateAndNameData')]
    public function testGetByTaxRateAndName(float $taxRate, string $name, ?string $expectedResult): void
    {
        $taxLookup = $this->getTaxLookup();

        static::assertSame($expectedResult, $taxLookup->getByTaxRateAndName($taxRate, $name, Context::createDefaultContext()));
    }

    #[DataProvider('getGetByTaxRateAndNameDatabaseData')]
    public function testGetByTaxRateAndNameShouldGetDataFromCache(float $taxRate, string $name, ?string $expectedResult): void
    {
        $taxLookup = $this->getMockedTaxLookup();

        static::assertSame($expectedResult, $taxLookup->getByTaxRateAndName($taxRate, $name, Context::createDefaultContext()));
    }

    #[DataProvider('getGetTaxRateData')]
    public function testGetTaxRate(string $uuid, ?float $expectedResult): void
    {
        $taxLookup = $this->getTaxLookup();

        static::assertSame($expectedResult, $taxLookup->getTaxRate($uuid, Context::createDefaultContext()));
    }

    #[DataProvider('getGetTaxRateDatabaseData')]
    public function testGetTaxRateShouldGetDataFromCache(string $uuid, ?float $expectedResult): void
    {
        $taxLookup = $this->getMockedTaxLookup();

        static::assertSame($expectedResult, $taxLookup->getTaxRate($uuid, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $taxLookup = $this->getMockedTaxLookup();

        $cacheProperty = new \ReflectionProperty(TaxLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($taxLookup));

        $taxLookup->reset();

        static::assertEmpty($cacheProperty->getValue($taxLookup));
    }

    /**
     * @return array<int, array{taxRate: float, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['taxRate' => 0.11, 'expectedResult' => null];
        $returnData[] = ['taxRate' => 0.21, 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{taxRate: float, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $list = self::getTaxRateList();

        $returnData = [];
        foreach ($list as $tax) {
            static::assertInstanceOf(TaxEntity::class, $tax);

            $returnData[] = [
                'taxRate' => $tax->getTaxRate(),
                'expectedResult' => $tax->getId(),
            ];
        }

        return $returnData;
    }

    /**
     * @return array<int, array{taxRate: float, expectedResult: string|null}>
     */
    public static function getGetByTaxRateAndNameData(): array
    {
        $returnData = self::getGetByTaxRateAndNameDatabaseData();
        $returnData[] = ['taxRate' => 0.11, 'name' => 'Foo', 'expectedResult' => null];
        $returnData[] = ['taxRate' => 0.21, 'name' => 'Bar', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{taxRate: float, name: string, expectedResult: string}>
     */
    public static function getGetByTaxRateAndNameDatabaseData(): array
    {
        $list = self::getTaxRateList();

        $returnData = [];
        foreach ($list as $tax) {
            static::assertInstanceOf(TaxEntity::class, $tax);

            $returnData[] = [
                'taxRate' => $tax->getTaxRate(),
                'name' => $tax->getName(),
                'expectedResult' => $tax->getId(),
            ];
        }

        return $returnData;
    }

    /**
     * @return array<int, array{uuid: string, expectedResult: float|null}>
     */
    public static function getGetTaxRateData(): array
    {
        $returnData = self::getGetTaxRateDatabaseData();
        $returnData[] = ['uuid' => Uuid::randomHex(), 'expectedResult' => null];
        $returnData[] = ['uuid' => Uuid::randomHex(), 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{uuid: string, expectedResult: float}>
     */
    public static function getGetTaxRateDatabaseData(): array
    {
        $list = self::getTaxRateList();

        $returnData = [];
        foreach ($list as $tax) {
            static::assertInstanceOf(TaxEntity::class, $tax);

            $returnData[] = [
                'uuid' => $tax->getId(),
                'expectedResult' => $tax->getTaxRate(),
            ];
        }

        return $returnData;
    }

    /**
     * @return EntityCollection<TaxEntity>
     */
    private static function getTaxRateList(): EntityCollection
    {
        $taxRepository = static::getContainer()->get('tax.repository');

        return $taxRepository->search(new Criteria(), Context::createDefaultContext())->getEntities();
    }

    private function getTaxLookup(): TaxLookup
    {
        $taxLookup = $this->getContainer()->get(TaxLookup::class);
        static::assertInstanceOf(TaxLookup::class, $taxLookup);

        return $taxLookup;
    }

    private function getMockedTaxLookup(): TaxLookup
    {
        $taxRepository = $this->createMock(EntityRepository::class);
        $taxRepository->method('search')->willThrowException(
            new \Exception('TaxLookup repository should not be called')
        );
        $taxLookup = new TaxLookup($taxRepository);

        $cacheReflectionProperty = new \ReflectionProperty(TaxLookup::class, 'cache');
        $cacheReflectionProperty->setAccessible(true);

        $taxRateCacheReflectionProperty = new \ReflectionProperty(TaxLookup::class, 'taxRateCache');
        $taxRateCacheReflectionProperty->setAccessible(true);

        $databaseData = self::getGetByTaxRateAndNameDatabaseData();
        $cacheData = [];
        $taxRateCache = [];
        foreach ($databaseData as $data) {
            $cacheData[$data['taxRate']] = $data['expectedResult'];
            $cacheData[$data['taxRate'] . '-' . $data['name']] = $data['expectedResult'];

            $taxRateCache[$data['expectedResult']] = $data['taxRate'];
        }

        $cacheReflectionProperty->setValue($taxLookup, $cacheData);
        $taxRateCacheReflectionProperty->setValue($taxLookup, $taxRateCache);

        return $taxLookup;
    }
}
