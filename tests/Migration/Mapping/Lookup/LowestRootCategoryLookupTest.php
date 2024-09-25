<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LowestRootCategoryLookup;

class LowestRootCategoryLookupTest extends TestCase
{
    use KernelTestBehaviour;

    public function testGet(): void
    {
        $lowestRootCategoryLookup = $this->getLowestRootCategoryLookup();
        $result = $lowestRootCategoryLookup->get(Context::createDefaultContext());

        static::assertSame($this->getGetTestExpectedResult(), $result);
    }

    public function testGetShouldReturnNull(): void
    {
        $lowestRootCategoryLookup = $this->getMockedLowestRootCategoryLookup();

        static::assertNull($lowestRootCategoryLookup->get(Context::createDefaultContext()));
    }

    public function testGetShouldGetDataFromCache(): void
    {
        $categoryRepository = $this->createMock(EntityRepository::class);
        $categoryRepository->method('search')->willThrowException(
            new \Exception('LowestRootCategoryLookup repository should not be called')
        );

        $lowestRootCategoryLookup = new LowestRootCategoryLookup($categoryRepository);
        $cacheProperty = new \ReflectionProperty(LowestRootCategoryLookup::class, 'cache');
        $cacheProperty->setAccessible(true);
        static::assertNull($cacheProperty->getValue($lowestRootCategoryLookup));

        $expectedResult = Uuid::randomHex();
        $cacheProperty->setValue($lowestRootCategoryLookup, $expectedResult);
        static::assertNotNull($cacheProperty->getValue($lowestRootCategoryLookup));

        $result = $lowestRootCategoryLookup->get(Context::createDefaultContext());

        static::assertSame($expectedResult, $result);
    }

    public function testReset(): void
    {
        $lowestRootCategoryLookup = $this->getLowestRootCategoryLookup();

        $cacheProperty = new \ReflectionProperty(LowestRootCategoryLookup::class, 'cache');
        $cacheProperty->setAccessible(true);
        static::assertNull($cacheProperty->getValue($lowestRootCategoryLookup));

        $randomUuid = Uuid::randomHex();
        $cacheProperty->setValue($lowestRootCategoryLookup, $randomUuid);
        static::assertNotNull($cacheProperty->getValue($lowestRootCategoryLookup));

        $lowestRootCategoryLookup->reset();

        static::assertNull($cacheProperty->getValue($lowestRootCategoryLookup));
    }

    private function getGetTestExpectedResult(): string
    {
        $categoryRepository = $this->getContainer()->get('category.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', null));

        $searchResult = $categoryRepository->search($criteria, Context::createDefaultContext());
        $result = $searchResult->getEntities()->sortByPosition()->last();
        static::assertInstanceof(CategoryEntity::class, $result);

        return $result->getId();
    }

    private function getLowestRootCategoryLookup(): LowestRootCategoryLookup
    {
        return new LowestRootCategoryLookup(
            $this->getContainer()->get('category.repository')
        );
    }

    private function getMockedLowestRootCategoryLookup(): LowestRootCategoryLookup
    {
        $categoryRepository = $this->createMock(EntityRepository::class);
        $categoryRepository->method('search')->willReturn(
            new EntitySearchResult(
                CategoryEntity::class,
                0,
                new CategoryCollection(),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        return new LowestRootCategoryLookup($categoryRepository);
    }
}
