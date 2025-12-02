<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CmsPageLookup;

class CmsPageLookupTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @param array<int, string> $names
     */
    #[DataProvider('getGetByNamesData')]
    public function testGetByNames(array $names, ?string $expectedResult): void
    {
        $cmsPageLookup = $this->getCmsPageLookup();

        static::assertSame($expectedResult, $cmsPageLookup->getByNames($names, Context::createDefaultContext()));
    }

    /**
     * @param array<int, string> $names
     */
    #[DataProvider('getGetByNamesDatabaseData')]
    public function testGetByNamesShouldGetDataFromCache(array $names, ?string $expectedResult): void
    {
        $cmsPageLookup = $this->getMockedCmsPageLookup();

        static::assertSame($expectedResult, $cmsPageLookup->getByNames($names, Context::createDefaultContext()));
    }

    /**
     * @param array<int, string> $names
     */
    #[DataProvider('getGetLockedByNamesAndTypeData')]
    public function testGetLockedByNamesAndType(array $names, string $type, ?string $expectedResult): void
    {
        $cmsPageLookup = $this->getCmsPageLookup();

        static::assertSame($expectedResult, $cmsPageLookup->getLockedByNamesAndType($names, $type, Context::createDefaultContext()));
    }

    /**
     * @param array<int, string> $names
     */
    #[DataProvider('getGetLockedByNamesAndTypeDatabaseData')]
    public function testGetLockedByNamesAndTypeShouldGetDataFromCache(array $names, string $type, ?string $expectedResult): void
    {
        $cmsPageLookup = $this->getMockedCmsPageLookup();

        static::assertSame($expectedResult, $cmsPageLookup->getLockedByNamesAndType($names, $type, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $cmsPageLookup = $this->getMockedCmsPageLookup();

        $cacheProperty = new \ReflectionProperty(CmsPageLookup::class, 'cache');
        $cacheProperty->setAccessible(true);
        static::assertNotEmpty($cacheProperty->getValue($cmsPageLookup));

        $cmsPageLookup->reset();

        static::assertEmpty($cacheProperty->getValue($cmsPageLookup));
    }

    /**
     * @return array<array{names: array<string|null>, type: string, expectedResult: string|null}>
     */
    public static function getGetLockedByNamesAndTypeData(): array
    {
        $resultData = self::getGetLockedByNamesAndTypeDatabaseData();
        $resultData[] = ['names' => ['foo', 'foo2'], 'type' => 'N/A', 'expectedResult' => null];
        $resultData[] = ['names' => ['bar', 'bar2'], 'type' => 'N/A', 'expectedResult' => null];

        return $resultData;
    }

    /**
     * @return array<array{names: array<string|null>, type: string, expectedResult: string}>
     */
    public static function getGetLockedByNamesAndTypeDatabaseData(): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('translations');
        $criteria->addFilter(new EqualsFilter('locked', true));

        $list = self::getContainer()->get('cms_page.repository')->search($criteria, Context::createDefaultContext())->getEntities();

        $resultData = [];

        foreach ($list as $cmsPage) {
            static::assertInstanceOf(CmsPageEntity::class, $cmsPage);
            $result = [];
            $result[] = $cmsPage->getName();

            foreach ($cmsPage->getTranslations() ?? [] as $translation) {
                $result[] = $translation->getName();
            }

            $resultData[] = ['names' => $result, 'type' => $cmsPage->getType(), 'expectedResult' => $cmsPage->getId()];
        }

        return $resultData;
    }

    /**
     * @return array<array{names: array<string|null>, expectedResult: string|null}>
     */
    public static function getGetByNamesData(): array
    {
        $resultData = self::getGetByNamesDatabaseData();
        $resultData[] = ['names' => ['foo', 'foo2'], 'expectedResult' => null];
        $resultData[] = ['names' => ['bar', 'bar2'], 'expectedResult' => null];

        return $resultData;
    }

    /**
     * @return array<array{names: array<string|null>, expectedResult: string}>
     */
    public static function getGetByNamesDatabaseData(): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('translations');
        $criteria->addFilter(new EqualsFilter('locked', false));

        $list = self::getContainer()->get('cms_page.repository')->search($criteria, Context::createDefaultContext())->getEntities();

        $resultData = [];

        foreach ($list as $cmsPage) {
            static::assertInstanceOf(CmsPageEntity::class, $cmsPage);
            $result = [];
            $result[] = $cmsPage->getName();

            foreach ($cmsPage->getTranslations() ?? [] as $translation) {
                $result[] = $translation->getName();
            }

            $resultData[] = ['names' => $result, 'expectedResult' => $cmsPage->getId()];
        }

        return $resultData;
    }

    private function getCmsPageLookup(): CmsPageLookup
    {
        return static::getContainer()->get(CmsPageLookup::class);
    }

    private function getMockedCmsPageLookup(): CmsPageLookup
    {
        $currencyRepository = $this->createMock(EntityRepository::class);
        $currencyRepository->method('search')->willThrowException(
            new \Exception('CmsPageLookup repository should not be called')
        );

        $cmsPageLookup = new CmsPageLookup($currencyRepository);

        $reflectionProperty = new \ReflectionProperty(CmsPageLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $namesDatabaseData = self::getGetByNamesDatabaseData();
        $namesAndTypeDatabaseData = self::getGetLockedByNamesAndTypeDatabaseData();

        $cacheData = [];
        foreach ($namesDatabaseData as $data) {
            $cacheData[\implode('-', $data['names'])] = $data['expectedResult'];
        }

        foreach ($namesAndTypeDatabaseData as $data) {
            $cacheData[\implode('-', $data['names']) . '-' . $data['type']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($cmsPageLookup, $cacheData);

        return $cmsPageLookup;
    }
}
