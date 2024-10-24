<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\SalesChannel\Sorting\ProductSortingEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Migration\Mapping\Lookup\ProductSortingLookup;

class ProductSortingLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $key, ?string $expectedResult): void
    {
        $productSortingLookup = $this->getProductSortingLookup();

        static::assertSame($expectedResult, $productSortingLookup->get($key, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $key, ?string $expectedResult): void
    {
        $productSortingLookup = $this->getMockedProductSortingLookup();

        static::assertSame($expectedResult, $productSortingLookup->get($key, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $productSortingLookup = $this->getMockedProductSortingLookup();

        $cacheProperty = new \ReflectionProperty(ProductSortingLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($productSortingLookup));

        $productSortingLookup->reset();

        static::assertEmpty($cacheProperty->getValue($productSortingLookup));
    }

    /**
     * @return array<int, array{key: string, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['key' => 'Foo', 'expectedResult' => null];
        $returnData[] = ['key' => 'Bar', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{key: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $list = self::getContainer()->get('product_sorting.repository')->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $productSorting) {
            static::assertInstanceOf(ProductSortingEntity::class, $productSorting);
            $returnData[] = ['key' => $productSorting->getKey(), 'expectedResult' => $productSorting->getId()];
        }

        return $returnData;
    }

    private function getProductSortingLookup(): ProductSortingLookup
    {
        return $this->getContainer()->get(ProductSortingLookup::class);
    }

    private function getMockedProductSortingLookup(): ProductSortingLookup
    {
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('searchIds')->willThrowException(new \Exception('ProductSortingLookup repository should not be called'));

        $productSortingLookup = new ProductSortingLookup($entityRepository);

        $reflectionProperty = new \ReflectionProperty(ProductSortingLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();

        $cache = [];
        foreach ($databaseData as $data) {
            $cache[$data['key']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($productSortingLookup, $cache);

        return $productSortingLookup;
    }
}
