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
     * @return array<int, array{taxRate: float, expectedResult: ?string}>
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
    private static function getDatabaseData(): array
    {
        $taxRepository = static::getContainer()->get('tax.repository');
        $list = $taxRepository->search(new Criteria(), Context::createDefaultContext());

        $returnData = [];
        foreach ($list->getEntities() as $tax) {
            $returnData[] = [
                'taxRate' => $tax->getTaxRate(),
                'expectedResult' => $tax->getId(),
            ];
        }

        return $returnData;
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

        $reflectionProperty = new \ReflectionProperty(TaxLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[$data['taxRate']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($taxLookup, $cacheData);

        return $taxLookup;
    }
}
